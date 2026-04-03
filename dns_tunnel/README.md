# DNS Message Server

Servidor DNS autoritativo que recebe mensagens codificadas em base64 nos subdomínios das queries DNS.

## Como funciona

```
Cliente                          Servidor DNS (porta 53)
  |                                    |
  |-- DNS query: dGVzdGU.fabio.com.br -->|
  |                                    |-- decodifica "dGVzdGU" → "teste"
  |                                    |-- salva em mensagens.txt
  |<-- DNS reply (A 127.0.0.1) --------|
```

### Mensagem simples (1 query)
```
<base64>.dominio.com
```

### Mensagem multi-parte (várias queries)
```
<base64_chunk>.<seq>-<total>-<msgid>.dominio.com
```
- `seq` = número da parte (começa em 0)
- `total` = total de partes
- `msgid` = identificador único da mensagem

## Setup

```bash
pip install -r requirements.txt
```

## Servidor

```bash
# Porta 53 requer root/sudo
sudo python dns_server.py --domain fabio.com.br --port 53 --output mensagens.txt

# Para teste local sem root, use porta alta
python dns_server.py --domain fabio.com.br --port 5353 --output mensagens.txt
```

### Opções do servidor
| Flag | Default | Descrição |
|------|---------|-----------|
| `--domain`, `-d` | (obrigatório) | Domínio base |
| `--port`, `-p` | 53 | Porta UDP/TCP |
| `--bind`, `-b` | 0.0.0.0 | IP para bind |
| `--output`, `-o` | mensagens.txt | Arquivo de saída |
| `--reply-ip` | 127.0.0.1 | IP retornado nas respostas |

## Cliente

```bash
# Mensagem simples
python dns_client.py -d fabio.com.br -s SEU_IP -m "teste"

# Mensagem longa (multi-parte automático)
python dns_client.py -d fabio.com.br -s SEU_IP -m "mensagem muito longa que será dividida em vários chunks"

# Enviar arquivo
python dns_client.py -d fabio.com.br -s SEU_IP -f arquivo.txt

# Via stdin
cat dados.txt | python dns_client.py -d fabio.com.br -s SEU_IP --stdin

# Porta customizada (teste local)
python dns_client.py -d fabio.com.br -s 127.0.0.1 -p 5353 -m "teste"
```

## Configuração no Registro.br

1. No painel do domínio, configure os **nameservers** apontando para o IP do seu servidor
2. Ou crie um registro **NS** delegando um subdomínio:
   ```
   msg.fabio.com.br  NS  ns1.fabio.com.br
   ns1.fabio.com.br  A   <IP_DO_SEU_SERVIDOR>
   ```
3. Execute o servidor na porta 53 do IP público

## Teste local rápido

Terminal 1 (servidor):
```bash
python dns_server.py -d fabio.com.br -p 5353
```

Terminal 2 (cliente):
```bash
python dns_client.py -d fabio.com.br -s 127.0.0.1 -p 5353 -m "hello world"
```

Terminal 3 (ou mesmo terminal):
```bash
# Também funciona com dig/nslookup
dig @127.0.0.1 -p 5353 dGVzdGU.fabio.com.br A
```
