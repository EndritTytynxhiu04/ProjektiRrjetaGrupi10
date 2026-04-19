# Sistemi TCP Client-Server me Monitorim HTTP (PHP)

## 1. Përmbledhje
Ky projekt implementon një sistem client-server duke përdorur socket TCP në PHP, duke demonstruar komunikimin me socket, menaxhimin e shumë klientëve, operacionet me file me kontroll të aksesit, dhe monitorimin në kohë reale të serverit përmes HTTP.

## 2. Komponentët e Sistemit
Sistemi përbëhet nga katër komponentë kryesorë:

- Serveri TCP (shtresa kryesore e komunikimit)
- Klienti TCP (shtresa e ndërveprimit me përdoruesin)
- Sistemi i Menaxhimit të File-ve dhe Lejeve
- Serveri i Monitorimit HTTP

Secili komponent punon së bashku për të krijuar një aplikacion funksional dhe interaktiv në rrjet.

### 2.1 Serveri TCP
Pranon shumë lidhje klientësh, vendos kufi për lidhjet, menaxhon timeout-et, regjistron mesazhet dhe përditëson të dhënat e përbashkëta për serverin HTTP.

### 2.2 Klienti TCP
Lidhet me serverin, autentifikohet me një token, dërgon komanda dhe mesazhe, dhe merr përgjigje. Mbështet role admin dhe readonly.

### 2.3 Menaxhimi i File-ve dhe Lejeve
Klientët admin kanë akses të plotë (lexim, shkrim, ekzekutim) dhe mund të përdorin të gjitha komandat. Klientët readonly janë të kufizuar vetëm në /list dhe /read.

### 2.4 Serveri i Monitorimit HTTP
Ekzekutohet në portin 9090 paralelisht me serverin TCP. Ofron statistika në kohë reale përmes GET /stats duke përfshirë lidhjet aktive, IP-të e klientëve, numrin e mesazheve dhe kohën e serverit.

## 3. Struktura e Projektit

```bash
project/
├── server.php
├── file_manager.php
├── http_server.php
├── stats.php
├── config.php
├── shared_data.json
└── client.php


## 4. Anëtarët e Grupit

| Anëtari | Roli |
|--------|------ |
| Endrit Tytynxhiu | Bërthama e Serverit & Menaxhimi i Lidhjeve |
| Enesa Buja | Sistemi i File-ve & Komandat Admin |
| Elmaze Murati | Pjesa e Klientit (Admin & Readonly) |
| Elsa Shaqiri | Serveri i Monitorimit HTTP |

## 5. Kërkesat

- PHP 7.4 ose më i ri
- XAMPP (ose ndonjë instalim tjetër PHP)
- Sockets extension i aktivizuar në PHP

## 6. Si të ekzekutohet

### Hapi 1 - Starto Serverin TCP
php server.php

### Hapi 2 - Starto Serverin HTTP për Monitorim
php http_server.php

### Hapi 3 - Lidhu si Klient Admin
php client_admin.php

### Hapi 4 - Lidhu si Klient Readonly
php client_readonly.php

## 7. Komandat dhe Kontrolli i Aksesit

### 7.1 Komandat e Adminit

| Komanda | Përshkrimi |
|---------|-------------|
| /list | Liston të gjitha file-t në server |
| /read <filename> | Lexon përmbajtjen e një file |
| /upload <filename> | Ngarkon një file në server |
| /download <filename> | Shkarkon një file nga serveri |
| /delete <filename> | Fshin një file nga serveri |
| /search <keyword> | Kërkon file sipas fjalës kyçe |
| /info <filename> | Tregon madhësinë dhe datat e file-it |

### 7.2 Kontrolli i aksesit

- Përdoruesit admin kanë të gjitha lejet (lexim, shkrim, ekzekutim)
- Përdoruesit e zakonshëm kanë vetëm leje leximi

## 8. Testimi i Sistemit

1. Starto serverin TCP
2. Lidhu me një ose më shumë klientë
3. Dërgo mesazhe ose ekzekuto komanda
4. Hap një browser dhe shko në:
   http://127.0.0.1:9090/stats

Nëse gjithçka është implementuar saktë, do të shfaqet një përgjigje JSON me statistika në kohë reale.

## 9. Workflow i Zhvillimit

- Secili anëtar ka punuar në një branch të veçantë
- Ndryshimet janë bërë commit rregullisht
- Repository është publik
- Funksionalitetet janë testuar para merge në main

## 10. Shënime

- Klientët admin marrin përgjigje më të shpejta se readonly
- File shared_data.json mund të përmbajë të dhëna testuese dhe përditësohet automatikisht nga serveri
- File-t e ngarkuar ruhen në server_files/
- Lidhjet joaktive mbyllen pas 1 minutë
- Klientët mund të rilidhen në çdo kohë

## 11. Përfundim

Ky projekt demonstron implementimin praktik të një sistemi në rrjet duke përdorur PHP. Ai integron komunikimin TCP me socket, menaxhimin e shumë klientëve, operacionet me file me kontroll aksesesh dhe monitorimin në kohë reale përmes HTTP. Sistemi final është plotësisht funksional dhe paraqet konceptet kryesore të rrjeteve kompjuterike në praktikë.

## 12. Licenca

Ky projekt është i licencuar nën licencën MIT.