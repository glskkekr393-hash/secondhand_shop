FROM php:8.2-apache

# ติดตั้ง MySQL / mysqli
RUN docker-php-ext-install mysqli

# ปิด MPM ทุกตัวก่อน
RUN a2dismod mpm_event || true
RUN a2dismod mpm_worker || true
RUN a2dismod mpm_prefork || true

# เปิด MPM ที่ PHP Apache ต้องใช้
RUN a2enmod mpm_prefork

# เปิด mod_rewrite
RUN a2enmod rewrite

# คัดลอกไฟล์เว็บไซต์
COPY . /var/www/html/

# ตั้งสิทธิ์ไฟล์
RUN chown -R www-data:www-data /var/www/html

# เปิดพอร์ต Apache
EXPOSE 80
