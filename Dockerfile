FROM php:8.2-apache

# ปิด MPM ตัวอื่น ป้องกัน Apache โหลด MPM ซ้ำ
RUN a2dismod mpm_event mpm_worker mpm_prefork || true

# เปิด MPM ที่ใช้กับ PHP และเปิด rewrite
RUN a2enmod mpm_prefork rewrite

# ติดตั้ง MySQLi
RUN docker-php-ext-install mysqli

# คัดลอกไฟล์เว็บไซต์
COPY . /var/www/html/

EXPOSE 80
