#!/usr/bin/env python3
"""
DNS Message Client - Envia mensagens via queries DNS.

Uso:
  # Mensagem curta (cabe em 1 query)
  python dns_client.py --domain fabio.com.br --server 1.2.3.4 --message "teste"

  # Mensagem de um arquivo
  python dns_client.py --domain fabio.com.br --server 1.2.3.4 --file dados.txt

  # Via stdin
  echo "hello world" | python dns_client.py --domain fabio.com.br --server 1.2.3.4 --stdin
"""

import argparse
import base64
import random
import socket
import string
import sys
import time

from dnslib import DNSRecord, QTYPE


def b64_encode(data: bytes) -> str:
    """Codifica em base64 url-safe sem padding."""
    return base64.b64encode(data).decode().rstrip("=")


def gen_msgid(length: int = 6) -> str:
    """Gera um ID aleatório para a mensagem."""
    return "".join(random.choices(string.ascii_lowercase + string.digits, k=length))


def send_dns_query(qname: str, server: str, port: int, timeout: float = 5.0):
    """Envia uma query DNS A e espera a resposta."""
    q = DNSRecord.question(qname, "A")
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.settimeout(timeout)
    try:
        sock.sendto(q.pack(), (server, port))
        data, _ = sock.recvfrom(4096)
        return DNSRecord.parse(data)
    except socket.timeout:
        print(f"  [TIMEOUT] {qname}")
        return None
    finally:
        sock.close()


def send_message(
    message: bytes,
    domain: str,
    server: str,
    port: int = 53,
    chunk_size: int = 50,
    delay: float = 0.1,
):
    """
    Envia uma mensagem via DNS queries.

    Se a mensagem cabe em 1 label (até chunk_size bytes), envia diretamente.
    Caso contrário, divide em partes e envia com metadados.

    DNS labels têm limite de 63 caracteres. Base64 expande ~33%, então
    chunk_size=45 gera labels de 60 chars em base64 (dentro do limite).
    """
    domain = domain.rstrip(".")

    if len(message) <= chunk_size:
        # Mensagem simples
        b64 = b64_encode(message)
        qname = f"{b64}.{domain}"
        print(f"Enviando mensagem simples: {qname}")
        resp = send_dns_query(qname, server, port)
        if resp:
            print("  [OK] Resposta recebida")
        return

    # Mensagem multi-parte
    chunks = []
    for i in range(0, len(message), chunk_size):
        chunks.append(message[i : i + chunk_size])

    total = len(chunks)
    msgid = gen_msgid()
    print(f"Enviando mensagem em {total} partes (msgid={msgid})")

    for seq, chunk in enumerate(chunks):
        b64 = b64_encode(chunk)
        # Formato: <base64>.<seq>-<total>-<msgid>.<domain>
        qname = f"{b64}.{seq}-{total}-{msgid}.{domain}"
        print(f"  [{seq + 1}/{total}] {qname}")
        resp = send_dns_query(qname, server, port)
        if resp:
            print(f"  [{seq + 1}/{total}] OK")
        else:
            print(f"  [{seq + 1}/{total}] FALHOU - retentando...")
            time.sleep(1)
            send_dns_query(qname, server, port)

        if delay > 0 and seq < total - 1:
            time.sleep(delay)

    print(f"Mensagem enviada ({len(message)} bytes em {total} partes)")


def main():
    parser = argparse.ArgumentParser(description="DNS Message Client")
    parser.add_argument(
        "--domain", "-d", required=True,
        help="Domínio base (ex: fabio.com.br)",
    )
    parser.add_argument(
        "--server", "-s", required=True,
        help="IP do servidor DNS",
    )
    parser.add_argument(
        "--port", "-p", type=int, default=53,
        help="Porta DNS (default: 53)",
    )
    parser.add_argument(
        "--message", "-m",
        help="Mensagem a enviar",
    )
    parser.add_argument(
        "--file", "-f",
        help="Arquivo para enviar",
    )
    parser.add_argument(
        "--stdin", action="store_true",
        help="Ler mensagem do stdin",
    )
    parser.add_argument(
        "--chunk-size", type=int, default=45,
        help="Tamanho máximo de cada chunk em bytes (default: 45)",
    )
    parser.add_argument(
        "--delay", type=float, default=0.1,
        help="Delay entre queries em segundos (default: 0.1)",
    )
    args = parser.parse_args()

    if args.message:
        data = args.message.encode("utf-8")
    elif args.file:
        with open(args.file, "rb") as f:
            data = f.read()
    elif args.stdin:
        data = sys.stdin.buffer.read()
    else:
        parser.error("Especifique --message, --file ou --stdin")
        return

    print(f"Dados: {len(data)} bytes")
    send_message(
        message=data,
        domain=args.domain,
        server=args.server,
        port=args.port,
        chunk_size=args.chunk_size,
        delay=args.delay,
    )


if __name__ == "__main__":
    main()
