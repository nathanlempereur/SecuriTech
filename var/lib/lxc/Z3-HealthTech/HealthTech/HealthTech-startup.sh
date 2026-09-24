#!/bin/bash

RED='\033[0;31m'
WHITE='\033[1;37m'
CYAN='\033[0;36m'
NC='\033[0m'
BOLD='\033[1m'

echo -e "${RED}${BOLD}"

# ASCII Art Module Médical
cat << 'EOF'
        _..._
      /       \
     |    _    |
     |  _| |_  |
     | |_   _| |
     |   |_|   |
      \       /
        `---'
EOF

echo -e "${WHITE}${BOLD}"
echo "=========================================================="
echo "    Z.O.N.E   H.E.A.L.T.H.T.E.C.H   -   MODULE MÉDICAL    "
echo "          INFIRMERIE ET SOINS DE L'ÉQUIPAGE               "
echo -e "==========================================================${NC}"
echo ""

# Statistiques internes du conteneur
UPTIME=$(uptime -p | sed 's/up //')
MEM_FREE=$(free -m | awk '/Mem:/ {print $4}')
DISK_USE=$(df -h / | awk 'NR==2 {print $5}')
IP_ADDR=$(ip -4 a show eth0 2>/dev/null | awk '/inet / {print $2}' | cut -d/ -f1 || echo "Non assignée")

echo -e "${BOLD}Statut Biologique   :${NC} ${RED}Signes vitaux stables${NC}"
echo -e "${BOLD}Temps de cycle      :${NC} $UPTIME"
echo -e "${BOLD}Stock Médical (RAM) :${NC} ${MEM_FREE} Mo disponibles"
echo -e "${BOLD}Baies de soin       :${NC} 2% plein"
echo -e "${BOLD}Liaison Centrale    :${NC} $IP_ADDR"
echo ""
echo "=========================================================="
echo ""
echo -e "${RED}"

echo "          [Entrer pour ouvrir le sas terminal]"
read -p ""
