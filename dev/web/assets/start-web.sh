#!/bin/bash

set -e

FOODSOFT_ROOT="/var/www/html/fc/foodsoft"

create_user_and_group() {
    EXT_UID=$EXT_UID || $(stat -c %u ${BASH_SOURCE})
    EXT_GID=$EXT_GID || $(stat -c %g ${BASH_SOURCE})
    if [ "${EXT_UID}" = 0 -o -z "${EXT_UID}" ]
    then
        echo >%2 "Invalid value for EXT_UID: ${EXT_UID}"
    fi
    if [ "${EXT_GID}" = 0 -o -z "${EXT_GID}" ]
    then
        echo >%2 "Invalid value for EXT_GID: ${EXT_GID}"
    fi
    
    if ! getent group user >/dev/null
    then
        groupadd -g ${EXT_GID} user
    fi

    if ! getent passwd ${EXT_USER} >/dev/null
    then
        useradd -g ${EXT_GID} -m -u ${EXT_UID} ${EXT_USER}
    fi
}

create_bin_devsh() {
    cat >/bin/devsh <<EOF
#!/bin/bash
set -e

if [ \$# -eq 0 ]
then
    set -- bash
fi

sudo -i -u ${EXT_USER} $* -- "\$@"
EOF
    chmod +x /bin/devsh
}

setup_sudo() {
    echo "${EXT_USER} ALL=(ALL) NOPASSWD: ALL" >>/etc/sudoers
}

setup_webdocs() {
    mkdir -p ${FOODSOFT_ROOT}

    chown -R ${EXT_USER} ${FOODSOFT_ROOT}

    ln -s /src/* ${FOODSOFT_ROOT}
}

enable_site() {
    ln -s /etc/apache2/sites-available/foodsoft.conf /etc/apache2/sites-enabled/
}

run-web() {
    su -c "apache2-foreground"
}

if [ ! -e /.bootstrapped ]
then
    create_user_and_group
    create_bin_devsh
    setup_sudo
    setup_webdocs
    enable_site

    touch /.bootstrapped
fi

run-web
