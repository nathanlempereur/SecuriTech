#!/bin/bash

DIR=/SecureTech/monitoring

liste_conteneurs=$(lxc-ls | tr -s " " " ")

mkdir -p /var/www/html/logs

#Dossier de cache en cas d'erreur
mkdir -p "$DIR"/.cache-error
dir_cache="$DIR"/.cache-error


for el in $(echo "$liste_conteneurs");do
	cp  /var/lib/lxc/"$el"/rootfs/var/log/apache2/access.log /var/www/html/logs/"$el"-access.log
	cp  /var/lib/lxc/"$el"/rootfs/var/log/apache2/error.log /var/www/html/logs/"$el"-error.log
done


#Montage de mon csv pour le dahboard
echo "Zones,IP-Zone,Status-Zone,User-Error,SSH-Error,tmp-Error,AuthLogs-Error" > /var/www/html/csv/zone.csv

for zone in $(echo "$liste_conteneurs");do

	#Le fichier chache de la zone
	cache="${dir_cache}/${zone}.txt"

	#Par défault on met en indéfinie les infos
	b="N/A"
        d="N/A"
        e="N/A"
        f="N/A"
        g="N/A"

	c=$(lxc-ls -f | grep "$zone" | awk '{print $2}')

	if [[ "$c" == "RUNNING" ]];then

		b=$(lxc-ls -f | grep "$zone" | awk '{print $5}')
		d=$(if [[ $(lxc-attach "$zone" -- /bin/bash -c 'cat /etc/passwd | grep "/bin/bash"' | wc | awk '{print $1}') != 1 ]];then echo "YES";else echo "NO";fi;)
		e=$(if [[ $(lxc-attach "$zone" -- /bin/bash -c 'ls /root/.ssh/ 2>/dev/null | wc -l') == 1 ]];then echo "YES";else echo "NO";fi;)
		f=$(if [[ $(lxc-attach "$zone" -- /bin/bash -c 'ls -l /proc/*/exe 2>/dev/null | grep -c "/tmp/"') != 0 ]];then echo "YES";else echo "NO";fi;)
		g=$(if [[ $(lxc-attach "$zone" -- /bin/bash -c 'stat -c %s /var/log/auth.log 2>/dev/null || echo "0"') == 0 ]];then echo "YES";else echo "NO";fi;)


		#d=Test si un user reel a été ajouté dans la zone
		#e=Test si un fichier de config ou clé ssh a été installer
		#f=Test si un exe (cheval de troie, virus, mineur de crypto) a été installer dans /tmp
		#g=Test si le fichier Auth.log a été vider pour effecer les traces
		if [[ "$d" == "YES" || "$e" == "YES" || "$f" == "YES" || "$g" == "YES" ]];then
                	#Sauvegarde des erreurs dans le cache
			echo "$b,$d,$e,$f,$g" > "$cache"
			lxc-stop -n "$zone"
			c="STOPPED"
		else
			#On supprime le fichier cache si il existe si aucune erreur machine allumer
			rm -f "$cache" 2>/dev/null
        	fi
	else
		#Si le serveur est off, on vérifie si y'a du cache
		if [[ -f "$cache" ]];then
			#On recharge les valeurs cache si y'a un fichier
			valeurs=$(cat "$cache" | tr "," " ")
			b=$(echo "$valeurs" | awk '{print $1}')
			d=$(echo "$valeurs" | awk '{print $2}')
			e=$(echo "$valeurs" | awk '{print $3}')
			f=$(echo "$valeurs" | awk '{print $4}')
			g=$(echo "$valeurs" | awk '{print $4}')

		fi
	fi
	echo "$zone,$b,$c,$d,$e,$f,$g" >> /var/www/html/csv/zone.csv
done


chown www-data:www-data -R /var/www/html/

#Zone		$zone
#IP-Zone	$b
#Status-Zone	$c
#User-Error	$d
#SSH-Error	$e
#tmp-Error	$f
#AuthLogs-Error	$g
