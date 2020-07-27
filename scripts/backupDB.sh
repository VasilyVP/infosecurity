 #!/bin/bash

 mysqldump -u backup_user -pcHw8HX%nuKua scanox_prod | gzip > /var/www/html/scanox.pro/archive/scanox_prod_$(date +%w).sql.gz