# PHP via FastCGI. Paths are identical in the FPM containers (same image, /srv/app).
fastcgi_split_path_info ^(.+\.php)(/.*)$;
include fastcgi_params;
fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
fastcgi_param DOCUMENT_ROOT $document_root;
fastcgi_param HTTP_PROXY "";
fastcgi_read_timeout 120s;
fastcgi_buffers 16 16k;
fastcgi_buffer_size 32k;
internal;
