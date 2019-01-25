#!/bin/bash

clear

echo Launch a CentOS 7.x instance 
echo \(CentOS Linux 7 x86_64 HVM EBS 1704_01 - ami-51076231\) 
echo Select instance type m3.medium, \(recommended\) click NEXT
echo Select "Protect against accidental termination", click NEXT
echo Update the volume size to 100Mb
echo Click NEXT and add a tag so you can find your instance later
echo Select a security group - I use "ALL TCP" and configure firewalld
echo click LAUNCH and select or create a key pair so you can log in for further configuration
echo 
echo Before going further, create a resolvable domain in DNS and ensure that is is resolvable.

echo open a shell and log in 
echo IE: ssh -i mykey.pem centos@ec2-52-191-177-235.us-west-2.compute.amazonaws.com

echo move this installer to that server and execute it there

# if running this manually, make sure you are sudo or root first

#sudo -s


echo "Enter the friendly name of this server (IE: \"my dev server\")"
read FNAME

echo "Enter the FQDN  (IE: \"myserver.home.net\") or press ENTER/RETURN for default"
read MYFQDN

echo "Enter the name of the system operator (IE: \"Bob Jones\")"
read USRNM

echo "Enter the email address of the above system operator (IE: \"bob@here.com\")"
read EMAIL

echo "What timezone is the server in? (EST,CST,MST,PST)"
read TZ


# Only needed for manual deployment.
# If runnng this script manually, uncomment and edit these lines
#export FNAME="My WH Consumer"
#export MYFQDN="112.187.187.35.bc.googleusercontent.com"
#export USRNM="Tom Mairs"
#export EMAIL="tom.mairs@sparkpost.com"
#export TZ=BST

   if [ $TZ = "EST" ]; then
      MYTZ="America/New_York"
   fi
   if [ $TZ = "CST" ]; then
      MYTZ="America/Chicago"
   fi
   if [ $TZ = "MST" ]; then
      MYTZ="America/Edmonton"
   fi
   if [ $TZ = "PST" ]; then
      MYTZ="America/Los_Angeles"
   fi
   if [ $TZ = "BST" ]; then
      MYTZ="Europe/London"
   fi
   if [ $MYTZ = "" ]; then
      MYTZ="America/Los_Angeles"
   fi

echo "PLEASE WAIT....."

 # Use $HOSTNAME instead
 # MYHOST=`hostname -f`
 if [ $MYFQDN="" ]; then
  MYFQDN=`hostname -f`
 fi 
 PUBLICIP=`curl -s checkip.dyndns.org|sed -e 's/.*Current IP Address: //' -e 's/<.*$//' `
 PRIVATEIP=`hostname -i`
  
echo
echo Using these settings:
echo HOSTNAME = $HOSTNAME
echo Public IP = $PUBLICIP
echo Private IP = $PRIVATEIP
echo Time Zone = $MYTZ
echo Owner = $USRNM $EMAIL
echo ServerName = $FNAME
echo FQDN = $MYFQDN

export DEFAULT=/opt/msys/ecelerity/etc/conf/default/

echo "Applying environment changes..."
echo "..............................."

echo 'export TZ=$MYTZ' >> /etc/profile
export TZ=$MYTZ

echo
echo "Updating existing packages..."
echo "..............................."
yum clean headers
yum clean packages
yum clean metadata

yum update -y

echo
echo "Adding required packages..."
echo "..............................."

yum -y install perl mcelog sysstat ntp gdb lsof.x86_64 wget yum-utils bind-utils telnet mlocate lynx unzip sudo firewalld 
yum -y install make gcc curl cpan mysql* php php-devel php-gd php-imap php-ldap php-mysql php-odbc php-xml php-xmlrpc php-pgsql
yum -y install httpd mod_ssl openssl postgresql postgresql-contrib postgresql-devel postgresql-server bzip2

# Install GIT
sudo yum install curl-devel expat-devel gettext-devel openssl-devel zlib-devel -y
sudo yum install gcc perl-ExtUtils-MakeMaker -y
cd /usr/src
wget https://www.kernel.org/pub/software/scm/git/git-2.9.3.tar.gz
tar xzf git-2.9.3.tar.gz 
cd git-2.9.3
make prefix=/usr/local/git all
make prefix=/usr/local/git install
export PATH=$PATH:/usr/local/git/bin
source /etc/bashrc

#Make sure it all stays up to date
#Run a yum update at 3AM daily
echo "0 3 * * * root /usr/bin/yum update -y >/dev/null 2>&1">/etc/cron.d/yum-updates


# Clean up services
systemctl stop iptables.service
systemctl stop ip6tables.service
systemctl mask iptables.service
systemctl mask ip6tables.service

systemctl enable ntpd.service
systemctl start  ntpd.service

systemctl stop  postfix.service
systemctl disable postfix.service

systemctl stop  qpidd.service
systemctl disable qpidd.service

# Set up the firewall
echo "ZONE=public
" >> /etc/sysconfig/network-scripts/ifcfg-eth0

systemctl stop firewalld
systemctl start firewalld.service
firewall-cmd --set-default-zone=public
firewall-cmd --zone=public --change-interface=eth0
firewall-cmd --zone=public --permanent --add-service=http
firewall-cmd --zone=public --permanent --add-service=https
firewall-cmd --zone=public --permanent --add-service=ssh
firewall-cmd --zone=public --permanent --add-service=smtp
firewall-cmd --zone=public --permanent --add-port=587/tcp
firewall-cmd --zone=public --permanent --add-port=81/tcp
systemctl enable firewalld
sudo firewall-cmd --reload

echo "$PRIVATEIP  $HOSTNAME
$PUBLICIP $MYFQDN" >> /etc/hosts


# Update the system environment
echo "
vm.max_map_count = 768000
net.core.rmem_default = 32768
net.core.wmem_default = 32768
net.core.rmem_max = 262144
net.core.wmem_max = 262144
fs.file-max = 250000
net.ipv4.ip_local_port_range = 5000 63000
net.ipv4.tcp_tw_reuse = 1
net.ipv4.tcp_tw_recycle = 1
kernel.shmmax = 68719476736
net.core.somaxconn = 1024
vm.nr_hugepages = 10
kernel.shmmni = 4096
" >> /etc/sysctl.conf

/sbin/sysctl -p /etc/sysctl.conf

# Turn off SE Linux
sed -i 's/SELINUX=enforcing/SELINUX=disabled/' /etc/selinux/config  
/usr/sbin/setenforce 0

# create cron jobs for file management
echo "*/05 * * * *    root   /bin/php   /var/www/html/whc/filer.php >/var/log/whc-filer.log">/etc/cron.d/whc-filer
echo "*/20 * * * *    root   /bin/php   /var/www/html/whc/whc-rotate.php >/var/log/whc-rotate.log">/etc/cron.d/whc-rotate

cd /var/tmp

# Prep HTTPD and POSTGRESQL
sudo postgresql-setup initdb
sudo systemctl start postgresql
sudo systemctl enable postgresql

mkdir /etc/ssl/private
chmod 700 /etc/ssl/private
openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/ssl/private/apache-selfsigned.key -out /etc/ssl/certs/apache-selfsigned.crt
# If you are manually pasting, WAIT HERE!

openssl dhparam -out /etc/ssl/certs/dhparam.pem 2048
# Note that this step takes a while...
cat /etc/ssl/certs/dhparam.pem | sudo tee -a /etc/ssl/certs/apache-selfsigned.crt
sed -i 's/SSLProtocol all -SSLv2/#SSLProtocol all -SSLv2/' /etc/httpd/conf.d/ssl.conf
sed -i 's/SSLCipherSuite/#SSLCipherSuite/' /etc/httpd/conf.d/ssl.conf
sed -i 's/SSLCertificateFile \/etc\/pki\/tls\/certs\/localhost.crt/SSLCertificateFile \/etc\/ssl\/certs\/apache-selfsigned.crt/' /etc/httpd/conf.d/ssl.conf
sed -i 's/SSLCertificateKeyFile \/etc\/pki\/tls\/private\/localhost.key/SSLCertificateKeyFile \/etc\/ssl\/private\/apache-selfsigned.key/' /etc/httpd/conf.d/ssl.conf

echo '
SSLCipherSuite EECDH+AESGCM:EDH+AESGCM:AES256+EECDH:AES256+EDH 
SSLProtocol All -SSLv2 -SSLv3 
SSLHonorCipherOrder On 
Header always set Strict-Transport-Security "max-age=63072000; includeSubdomains" 
Header always set X-Frame-Options DENY 
Header always set X-Content-Type-Options nosniff 
SSLCompression off 
SSLUseStapling on 
SSLStaplingCache "shmcb:logs/stapling-cache(150000)" 
'>>/etc/httpd/conf.d/ssl.conf


sed -i 's/memory_limit = 128M/memory_limit = 1024M/' /etc/php.ini
sudo systemctl start httpd
sudo systemctl enable httpd

mkdir -p /var/www/html/whc/data
cd /var/www/html/whc
chown apache.apache ./data
chmod 755 ./data

mkdir -p /var/log/ecelerity/
chown -R apache.apache /var/log/ecelerity/
chmod 755 /var/log/ecelerity/


echo 'DONE!'
echo

