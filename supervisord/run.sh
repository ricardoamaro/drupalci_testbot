docker run -i -t -p 22 -p 8080:80 -p 3306 -volumes-from my-docroot jthorson/supervisord-test /bin/bash
