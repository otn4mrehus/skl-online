# SKL-Online
Kelulusan Online dengan "single code php", mempermudah sekolah yang ingin mengumumkan secara online dapat dilakukan dengan kode sederhana berikut.
Silakan kembangkan lebih lanjut.

## Struktur File & Direktori
```
+ skl-online/
  + assets/
    + images/
      - logo.png/jpg
    + js/
    + css/
  + upload/
     - nama_nisn.pdf
     - nama_nisn.png/jpg
  - index.php
  - siswa.csv  
```
##  Instalasi
```
git clone ](https://github.com/otn4mrehus/skl-online.git && \
cd skl-online && \
mkdir -p assets/images assets/js assets/css upload && \
sudo chmod -R 777 /opt/lampp/htdocs/skl-online/assets/ && \
sudo chown -R www-data:www-data /opt/lampp/htdocs/skl-online/assets/ && \
sudo chmod -R 777 /opt/lampp/htdocs/skl-online/upload/ && \
sudo chown -R www-data:www-data /opt/lampp/htdocs/skl-online/upload/ && \
```
## Running
```
http://up_address/skl-online
```

## Opsional
Mmepermudah pembuatan direktori dan hak akses, bisa dicoba script 
```
http://up_address/skl-online/newdir.php
http://up_address/skl-online/listdir.php
```
