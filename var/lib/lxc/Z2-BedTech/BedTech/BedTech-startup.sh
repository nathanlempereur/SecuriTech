#!/bin/bash

BLUE='\033[0;34m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m'
BOLD='\033[1m'

echo -e "${BLUE}${BOLD}"

# ASCII Art Module d'habitation / Capsule de stase
cat << 'EOF'
        .======.
       /  zZz   \
      |   ___    |
      |  |   |   |
      '=========='
EOF

echo -e "${CYAN}${BOLD}"
echo "=========================================================="
echo "    Z.O.N.E   B.E.D.T.E.C.H   -   MODULE HABITATION       "
echo "          DORTOIRS ET SUPPORT VITAL ÉQUIPAGE              "
echo -e "==========================================================${NC}"
echo ""

# Statistiques internes du conteneur
UPTIME=$(uptime -p | sed 's/up //')
MEM_FREE=$(free -m | awk '/Mem:/ {print $4}')
DISK_USE=$(df -h / | awk 'NR==2 {print $5}')
IP_ADDR=$(ip -4 a show eth0 2>/dev/null | awk '/inet / {print $2}' | cut -d/ -f1 || echo "Non assignée")

echo -e "${BOLD}Statut Équipage     :${NC} ${CYAN}Cycle de repos optimal${NC}"
echo -e "${BOLD}Temps de cycle      :${NC} $UPTIME"
echo -e "${BOLD}Support Vital (RAM) :${NC} ${MEM_FREE} Mo disponibles"
echo -e "${BOLD}Occupation Dortoirs :${NC} $DISK_USE plein"
echo -e "${BOLD}Liaison Centrale    :${NC} $IP_ADDR"
echo ""
echo "=========================================================="
echo ""
echo -e "${CYAN}"

echo "          [Entrer pour ouvrir le sas terminal]"
read -p ""
