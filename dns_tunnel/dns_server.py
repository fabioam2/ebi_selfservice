#!/usr/bin/env python3
"""
DNS Message Server - Recebe mensagens via queries DNS.

Protocolo:
  Mensagem simples (1 query):
    <base64>.dominio.com  →  decodifica e salva

  Mensagem multi-parte (várias queries):
    <base64>.<seq>-<total>-<msgid>.dominio.com
    seq   = número da parte (começa em 0)
    total = total de partes
    msgid = identificador da mensagem

  O base64 usa URL-safe encoding (- e _ no lugar de + e /)
  e sem padding (=).

Uso:
  python dns_server.py --domain fabio.com.br --port 53 --output mensagens.txt
"""

import argparse
import base64
import datetime
import logging
import os
import socket
import struct
import threading
from collections import defaultdict

from dnslib import DNSRecord, DNSHeader, RR, QTYPE, A

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
)
log = logging.getLogger("dns-msg-server")

# ─── Armazenamento de mensagens multi-parte ────────────────────────────────

class MessageStore:
    """Guarda partes de mensagens e monta quando todas chegam."""

    def __init__(self, output_file: str):
        self.output_file = output_file
        self.lock = threading.Lock()
        # msgid -> { "total": int, "parts": { seq: bytes } }
        self.pending: dict[str, dict] = defaultdict(
            lambda: {"total": None, "parts": {}}
        )

    def add_single(self, data: bytes):
        """Salva mensagem de uma única parte."""
        self._write(data)

    def add_part(self, msgid: str, seq: int, total: int, data: bytes):
        """Adiciona uma parte. Se a mensagem estiver completa, salva."""
        with self.lock:
            entry = self.pending[msgid]
            entry["total"] = total
            entry["parts"][seq] = data
            log.info(
                "msg=%s parte %d/%d (%d bytes)",
                msgid, seq + 1, total, len(data),
            )
            if len(entry["parts"]) == total:
                # Monta a mensagem na ordem
                full = b"".join(entry["parts"][i] for i in range(total))
                del self.pending[msgid]
                self._write(full)
                log.info("msg=%s completa (%d bytes total)", msgid, len(full))

    def _write(self, data: bytes):
        timestamp = datetime.datetime.now().isoformat()
        try:
            text = data.decode("utf-8")
        except UnicodeDecodeError:
            text = data.hex()

        line = f"[{timestamp}] {text}\n"
        with open(self.output_file, "a") as f:
            f.write(line)
        log.info("Salvo: %s", text[:120])


# ─── Decodificação base64 url-safe ─────────────────────────────────────────

def decode_b64(s: str) -> bytes:
    """Decodifica base64 url-safe, adicionando padding se necessário."""
    s = s.replace("-", "+").replace("_", "/")
    padding = 4 - len(s) % 4
    if padding != 4:
        s += "=" * padding
    return base64.b64decode(s)


# ─── Parse do subdomain ────────────────────────────────────────────────────

def parse_query(qname: str, domain: str):
    """
    Extrai dados da query DNS.

    Retorna:
      ("single", data_bytes)
      ("multi", msgid, seq, total, data_bytes)
      None se não for do nosso domínio
    """
    qname = qname.rstrip(".")
    domain = domain.rstrip(".")

    if not qname.lower().endswith("." + domain.lower()):
        return None

    # Remove o domínio base, ficando só com os subdomains
    prefix = qname[: -(len(domain) + 1)]  # tira ".dominio.com"
    labels = prefix.split(".")

    if not labels or not labels[0]:
        return None

    if len(labels) == 1:
        # Mensagem simples: <base64>.dominio.com
        data = decode_b64(labels[0])
        return ("single", data)

    if len(labels) == 2:
        # Multi-parte: <base64>.<seq>-<total>-<msgid>.dominio.com
        b64_part = labels[0]
        meta = labels[1]
        parts = meta.split("-")
        if len(parts) == 3:
            try:
                seq = int(parts[0])
                total = int(parts[1])
                msgid = parts[2]
                data = decode_b64(b64_part)
                return ("multi", msgid, seq, total, data)
            except (ValueError, Exception):
                pass

    # Fallback: junta todos os labels como base64
    b64_all = "".join(labels)
    data = decode_b64(b64_all)
    return ("single", data)


# ─── Servidor DNS ──────────────────────────────────────────────────────────

class DNSServer:
    def __init__(self, domain: str, port: int, bind: str, output: str, reply_ip: str):
        self.domain = domain
        self.port = port
        self.bind = bind
        self.reply_ip = reply_ip
        self.store = MessageStore(output)

    def handle(self, data: bytes, addr: tuple) -> bytes:
        try:
            request = DNSRecord.parse(data)
        except Exception:
            log.warning("Pacote DNS inválido de %s", addr)
            return b""

        qname = str(request.q.qname)
        qtype = QTYPE[request.q.qtype]
        log.info("Query: %s (%s) de %s", qname, qtype, addr)

        # Processa a mensagem
        result = parse_query(qname, self.domain)
        if result:
            if result[0] == "single":
                self.store.add_single(result[1])
            elif result[0] == "multi":
                _, msgid, seq, total, chunk = result
                self.store.add_part(msgid, seq, total, chunk)

        # Monta resposta DNS válida (responde com IP dummy)
        reply = request.reply()
        reply.add_answer(
            RR(qname, QTYPE.A, rdata=A(self.reply_ip), ttl=60)
        )
        return reply.pack()

    def run(self):
        sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        sock.bind((self.bind, self.port))
        log.info(
            "DNS Message Server rodando em %s:%d | domínio: %s | saída: %s",
            self.bind, self.port, self.domain, self.store.output_file,
        )
        log.info("Aguardando mensagens...")

        while True:
            try:
                data, addr = sock.recvfrom(4096)
                reply = self.handle(data, addr)
                if reply:
                    sock.sendto(reply, addr)
            except KeyboardInterrupt:
                log.info("Encerrando...")
                break
            except Exception as e:
                log.error("Erro: %s", e)


# ─── TCP handler (DNS over TCP) ───────────────────────────────────────────

    def run_tcp(self):
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        sock.bind((self.bind, self.port))
        sock.listen(16)
        log.info("TCP DNS listener em %s:%d", self.bind, self.port)

        while True:
            try:
                conn, addr = sock.accept()
                threading.Thread(
                    target=self._handle_tcp, args=(conn, addr), daemon=True
                ).start()
            except KeyboardInterrupt:
                break

    def _handle_tcp(self, conn: socket.socket, addr: tuple):
        try:
            # DNS over TCP: 2 bytes de tamanho + payload
            length_data = conn.recv(2)
            if len(length_data) < 2:
                return
            length = struct.unpack("!H", length_data)[0]
            data = conn.recv(length)
            reply = self.handle(data, addr)
            if reply:
                conn.sendall(struct.pack("!H", len(reply)) + reply)
        except Exception as e:
            log.error("TCP erro: %s", e)
        finally:
            conn.close()

    def start(self):
        """Inicia UDP e TCP em threads separadas."""
        udp_thread = threading.Thread(target=self.run, daemon=True)
        tcp_thread = threading.Thread(target=self.run_tcp, daemon=True)
        udp_thread.start()
        tcp_thread.start()
        log.info("Servidor pronto (UDP + TCP)")
        try:
            udp_thread.join()
        except KeyboardInterrupt:
            log.info("Encerrando...")


# ─── Main ──────────────────────────────────────────────────────────────────

def main():
    parser = argparse.ArgumentParser(description="DNS Message Server")
    parser.add_argument(
        "--domain", "-d", required=True,
        help="Domínio base (ex: fabio.com.br)",
    )
    parser.add_argument(
        "--port", "-p", type=int, default=53,
        help="Porta DNS (default: 53)",
    )
    parser.add_argument(
        "--bind", "-b", default="0.0.0.0",
        help="IP para bind (default: 0.0.0.0)",
    )
    parser.add_argument(
        "--output", "-o", default="mensagens.txt",
        help="Arquivo de saída (default: mensagens.txt)",
    )
    parser.add_argument(
        "--reply-ip", default="127.0.0.1",
        help="IP na resposta DNS (default: 127.0.0.1)",
    )
    args = parser.parse_args()

    server = DNSServer(
        domain=args.domain,
        port=args.port,
        bind=args.bind,
        output=args.output,
        reply_ip=args.reply_ip,
    )
    server.start()


if __name__ == "__main__":
    main()
