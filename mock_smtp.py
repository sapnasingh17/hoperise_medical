import socket
import threading
import sys

def handle_client(conn, addr):
    print(f"[{addr[0]}:{addr[1]}] Connected")
    try:
        conn.sendall(b"220 Mock SMTP Server Ready\r\n")
        while True:
            data = conn.recv(4096)
            if not data:
                break
            msg = data.decode('utf-8', errors='ignore')
            if msg.startswith("EHLO") or msg.startswith("HELO"):
                conn.sendall(b"250-localhost\r\n250-AUTH LOGIN PLAIN\r\n250 8BITMIME\r\n")
            elif msg.startswith("AUTH"):
                conn.sendall(b"334 VXNlcm5hbWU6\r\n") # Username prompt
                conn.recv(4096) # Get username
                conn.sendall(b"334 UGFzc3dvcmQ6\r\n") # Password prompt
                conn.recv(4096) # Get password
                conn.sendall(b"235 Authentication successful\r\n")
            elif msg.startswith("MAIL FROM:"):
                conn.sendall(b"250 2.1.0 OK\r\n")
            elif msg.startswith("RCPT TO:"):
                conn.sendall(b"250 2.1.5 OK\r\n")
            elif msg.startswith("DATA"):
                conn.sendall(b"354 Start mail input; end with <CRLF>.<CRLF>\r\n")
                # Read until \r\n.\r\n
                buffer = ""
                while "\r\n.\r\n" not in buffer:
                    chunk = conn.recv(4096).decode('utf-8', errors='ignore')
                    if not chunk:
                        break
                    buffer += chunk
                conn.sendall(b"250 2.0.0 OK: Message accepted\r\n")
                print(f"[{addr[0]}:{addr[1]}] Accepted message of length {len(buffer)}")
            elif msg.startswith("QUIT"):
                conn.sendall(b"221 2.0.0 Bye\r\n")
                break
            else:
                conn.sendall(b"250 OK\r\n")
    except Exception as e:
        print(f"[{addr[0]}:{addr[1]}] Error: {e}")
    finally:
        conn.close()
        print(f"[{addr[0]}:{addr[1]}] Disconnected")

def main():
    server = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    server.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    try:
        server.bind(('127.0.0.1', 1025))
        server.listen(5)
        print("Mock SMTP server listening on 127.0.0.1:1025...")
        while True:
            conn, addr = server.accept()
            t = threading.Thread(target=handle_client, args=(conn, addr))
            t.daemon = True
            t.start()
    except Exception as e:
        print(f"Server error: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()
