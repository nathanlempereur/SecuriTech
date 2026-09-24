#!/bin/bash

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'
BOLD='\033[1m'

echo -e "${YELLOW}${BOLD}"


cat << 'EOF'
               _-o#&&*''''?d:>b\_
          _o/"`''  '',, dMF9MMMMMHo_
       .o&#'        `"MbHMMMMMMMMMMMHo.
     .o"" '         vodM*$&&HMMMMMMMMMM?.
    ,'              $M&ood,~'`(&##MMMMMMH\
   /               ,MMMMMMM#b?#bobMMMMHMMML
  &              ?MMMMMMMMMMMMMMMMM7MMM$R*Hk
 ?$.            :MMMMMMMMMMMMMMMMMMM/HMMM|`*L
|               |MMMMMMMMMMMMMMMMMMMMbMH'   T,
$H#:            `*MMMMMMMMMMMMMMMMMMMMb#}'  `?
]MMH#             ""*""""*#MMMMMMMMMMMMM'    -
MMMMMb_                   |MMMMMMMMMMMP'     :
HMMMMMMMHo                 `MMMMMMMMMT       .
?MMMMMMMMP                  9MMMMMMMM}       -
-?MMMMMMM                  |MMMMMMMMM?,d-    '
 :|MMMMMM-                 `MMMMMMMT .M|.   :
  .9MMM[                    &MMMMM*' `'    .
   :9MMk                    `MMM#"        -
     &M}                     `          .-
      `&.                             .
        `~,   .                     ./
            . _                  .-
              '`--._,dd###pp=""'
EOF

echo -e "${CYAN}${BOLD}"

echo "=========================================================="
echo "    S.Y.S.T.E.M.E   S.E.C.U.R.I.T.E.C.H   -   2.0.8.0     "
echo "            SERVEUR CENTRAl - CONTRÔLE DES ZONES          "
echo -e "==========================================================${NC}"
echo ""

UPTIME=$(uptime -p | sed 's/up //')
MEM_FREE=$(free -m | awk '/Mem:/ {print $4}')
TEMP=$(vcgencmd measure_temp 2>/dev/null | sed 's/temp=//' || echo "N/A")

echo -e "${BOLD}Statut Cœur Central :${NC} Opérationnel"
echo -e "${BOLD}Température Cœur    :${NC} $TEMP"
echo -e "${BOLD}Temps de vol        :${NC} $UPTIME"
echo -e "${BOLD}Mémoire disponible  :${NC} ${MEM_FREE} Mo"
echo ""
echo -e "Dossier SecureTech  :${YELLOW} /SecureTech ${NC}"
echo ""
echo -e "${CYAN}--- STATUT DES ZONES (CONTENEURS) ---${NC}"
echo -e "${BOLD}ZONE (NOM)        ÉTAT           LIAISON (PING)     IP IPv4${NC}"
echo "------------------------------------------------------------------"

CONTAINERS=$(lxc-ls -1)

if [ -z "$CONTAINERS" ]; then
    echo -e "${YELLOW}Aucune zone détectée dans l'architecture matérielle.${NC}"
else
    for CT in $CONTAINERS; do
        STATE=$(lxc-info -n "$CT" -s | awk '{print $2}')
        
        if [ "$STATE" == "RUNNING" ]; then
            STATE_TEXT="[ ACTIF ]"
            STATE_COLOR="${GREEN}"
            
            IP=$(lxc-info -n "$CT" -i | awk '{print $2}' | head -n 1)
            
            if [ -n "$IP" ]; then
                if ping -c 1 -W 1 "$IP" > /dev/null 2>&1; then
                    PING_TEXT="CONNECTÉ"
                    PING_COLOR="${GREEN}"
                else
                    PING_TEXT="PERTE CO"
                    PING_COLOR="${RED}"
                fi
            else
                IP="Non assignée"
                PING_TEXT="EN ATTENTE"
                PING_COLOR="${YELLOW}"
            fi
        else
            STATE_TEXT="[HORS-LIGNE]"
            STATE_COLOR="${RED}"
            IP="N/A"
            PING_TEXT="INJOIGNABLE"
            PING_COLOR="${RED}"
        fi

        # Les couleurs sont injectées hors de l'espacement %s pour préserver l'alignement
        printf "%-17s ${STATE_COLOR}%-14s${NC} ${PING_COLOR}%-18s${NC} %s\n" "$CT" "$STATE_TEXT" "$PING_TEXT" "$IP"
    done
fi

echo "=================================================================="
echo ""
echo -e "${GREEN}"

echo "
		[Entrer pour continuer]"

read -p ""
