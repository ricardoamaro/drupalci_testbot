FROM centos

# Install EPEL6 for additional packages
RUN yum -y install http://mirror.pnl.gov/epel/6/i386/epel-release-6-8.noarch.rpm

# Update to the latest/greatest packages
RUN yum -y update

# Install Postgresql server
RUN yum -y install postgresql-server postgresql

# Initialize the database
# Start PostgreSQL
# As the "postgres" system user, create the 'drupaltestbot' role with a password of 'drupaltestbotpw'
RUN su - postgres -c /usr/bin/initdb \ 
 && su -l postgres -c '/usr/bin/pg_ctl -D /var/lib/pgsql/data -l pgstartup.log start' \
 && sleep 5 \
 && runuser -l postgres -c 'createuser -d -E -S -R -l -I drupaltestbot' \
 && runuser -l postgres -c "psql postgres -c \"ALTER USER drupaltestbot WITH ENCRYPTED PASSWORD 'drupaltestbotpw'\"" \
 && su -l postgres -c '/usr/bin/pg_ctl -D /var/lib/pgsql/data stop'

# Set PostgreSQL to automagically start on boot
RUN chkconfig postgresql on

# Set PostgreSQL to listen on all interfaces, and not just on localhost
RUN echo "listen_addresses='*'" >> /var/lib/pgsql/data/postgresql.conf

USER root
# Expose the PostgreSQL port
EXPOSE 5432

CMD ["/bin/su", "postgres", "-c", "/usr/bin/postgres -D /var/lib/pgsql/data -c config_file=/var/lib/pgsql/data/postgresql.conf"]
