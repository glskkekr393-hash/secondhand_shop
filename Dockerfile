FROM php:8.2-apache

# ติดตั้ง MySQL/MariaDB extension สำหรับ PHP
RUN docker-php-ext-install mysqli

# ล้าง Apache MPM ที่อาจเปิดซ้ำ
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load \
          /etc/apache2/mods-enabled/mpm_*.conf

# เปิด MPM แค่ตัวเดียว
RUN a2enmod mpm_prefork

# เปิด URL Rewrite
RUN a2enmod rewrite

# คัดลอกไฟล์เว็บไซต์
COPY . /var/www/html/

# ตั้งสิทธิ์ไฟล์เว็บไซต์
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
