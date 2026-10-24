# Shared by both server blocks in platform.conf.template.
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;

location = /healthz {
    access_log off;
    default_type text/plain;
    return 200 "ok\n";
}

# Vite build, published Statamic CP assets, uploads (public/assets volume): hashed names → long cache.
location ~ ^/(build|vendor|assets|storage)/ {
    add_header X-Content-Type-Options "nosniff" always;
    add_header Cache-Control $static_cache always;
    try_files $uri =404;
}

location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location = /favicon.ico { access_log off; log_not_found off; }
location = /robots.txt  { access_log off; log_not_found off; }

# Any other PHP file and dotfiles are never served.
location ~ \.php$ { return 404; }
location ~ /\.(?!well-known).* { deny all; }

error_page 404 /index.php;
