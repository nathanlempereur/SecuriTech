#!/bin/bash

YELLOW='\033[1;33m'
RED='\033[0;31m'
WHITE='\033[1;37m'
NC='\033[0m'
BOLD='\033[1m'

echo -e "${YELLOW}${BOLD}"

# ASCII Art Batterie / Générateur
cat << 'EOF'
          ___
        _[ + ]_
       |       |
       |   /   |
       |  /    |
       |  \    |
       |   \   |
       |_______|
EOF

echo -e "${WHITE}${BOLD}"
echo "=========================================================="
echo "    Z.O.N.E   E.N.E.R.G.Y.T.E.C.H   -   MODULE ÉNERGIE    "
echo "          GÉNÉRATEUR CENTRAL ET DISTRIBUTION              "
echo -e "==========================================================${NC}"
echo ""

# Statistiques internes du conteneur
UPTIME=$(uptime -p | sed 's/up //')
MEM_FREE=$(free -m | awk '/Mem:/ {print $4}')
DISK_USE=$(df -h / | awk 'NR==2 {print $5}')
IP_ADDR=$(ip -4 a show eth0 2>/dev/null | awk '/inet / {print $2}' | cut -d/ -f1 || echo "Non assignée")

echo -e "${BOLD}Statut Reacteur     :${NC} ${YELLOW}Actif - Haute Tension${NC}"
echo -e "${BOLD}Temps de cycle      :${NC} $UPTIME"
echo -e "${BOLD}Reserve Flux        :${NC} 100%"
echo -e "${BOLD}Cellules d'energie  :${NC} 8/8"
echo -e "${BOLD}Conssomation        :${NC} 359Kw/h"
echo -e "${BOLD}Liaison Centrale    :${NC} $IP_ADDR"
echo ""
echo "=========================================================="
echo ""
echo -e "${YELLOW}"

echo "          [Entrer pour ouvrir le sas terminal]"
read -p ""
