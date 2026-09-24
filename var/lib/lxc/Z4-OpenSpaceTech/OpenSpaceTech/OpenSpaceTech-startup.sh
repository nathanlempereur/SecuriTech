#!/bin/bash

MAGENTA='\033[0;35m'
WHITE='\033[1;37m'
CYAN='\033[0;36m'
NC='\033[0m'
BOLD='\033[1m'

echo -e "${MAGENTA}${BOLD}"

# ASCII Art Table de commandement / Hub de communication
cat << 'EOF'
        _______________
       /               \
      |  [===]   [===]  |
      |    ___   ___    |
       \_______________/
             |   |
            -------
EOF

echo -e "${CYAN}${BOLD}"
echo "=========================================================="
echo "    Z.O.N.E   O.P.E.N.S.P.A.C.E   -   MODULE DE TRAVAIL   "
echo "          ESPACE COMMUN ET COMMANDES SECONDAIRES          "
echo -e "==========================================================${NC}"
echo ""

# Statistiques internes du conteneur
UPTIME=$(uptime -p | sed 's/up //')
MEM_FREE=$(free -m | awk '/Mem:/ {print $4}')
DISK_USE=$(df -h / | awk 'NR==2 {print $5}')
IP_ADDR=$(ip -4 a show eth0 2>/dev/null | awk '/inet / {print $2}' | cut -d/ -f1 || echo "Non assignée")

echo -e "${BOLD}Statut Social       :${NC} ${MAGENTA}Actif - Liaisons inter-zones OK${NC}"
echo -e "${BOLD}Temps de cycle      :${NC} $UPTIME"
echo -e "${BOLD}Unites de cafe restantes    :${NC} 5Kg"
echo -e "${BOLD}Archives Locales    :${NC} $DISK_USE plein"
echo -e "${BOLD}Fauteuils disponibles    :${NC} 6/25"
echo -e "${BOLD}Liaison Centrale    :${NC} $IP_ADDR"

echo ""
echo "=========================================================="
echo ""
echo -e "${MAGENTA}"

echo "          [Entrer pour ouvrir le sas terminal]"
read -p ""
