FROM php:8.2-apache

# 安裝 mysqli
RUN docker-php-ext-install mysqli

# 啟用 Apache rewrite（保險用）
RUN a2enmod rewrite
