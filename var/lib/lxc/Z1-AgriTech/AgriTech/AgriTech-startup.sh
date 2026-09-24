#!/bin/bash

GREEN='\033[0;32m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
NC='\033[0m'
BOLD='\033[1m'

echo -e "${GREEN}${BOLD}"

# ASCII Art Bio-Dome / Plante
cat << 'EOF'
           .oOo.
          (     )
           `---'
             |
           .-|-.
          /  |  \
         '   |   '
            ===
EOF

echo -e "${CYAN}${BOLD}"
echo "=========================================================="
echo "    Z.O.N.E   A.G.R.I.T.E.C.H   -   MODULE DE SURVIE      "
echo "          PRODUCTION ALIMENTAIRE ET BOTANIQUE             "
echo -e "==========================================================${NC}"
echo ""

# Statistiques internes du conteneur
UPTIME=$(uptime -p | sed 's/up //')
MEM_FREE=$(free -m | awk '/Mem:/ {print $4}')
DISK_USE=$(df -h / | awk 'NR==2 {print $5}')
IP_ADDR=$(ip -4 a show eth0 2>/dev/null | awk '/inet / {print $2}' | cut -d/ -f1 || echo "Non assign√©e")

echo -e "${BOLD}Statut√Ecologique    :${NC} ${GREEN}Production Nominale${NC}"
echo -e "${BOLD}Temps de cycle      :${NC} $UPTIME"
echo -e "${BOLD}Ressources Eau/RAM  :${NC} ${MEM_FREE} Mo disponibles"
echo -e "${BOLD}Capacite des Silos  :${NC} $DISK_USE plein"
echo -e "${BOLD}Taux d'humidite     :${NC} 70%"
echo -e "${BOLD}Unitees disponibles :${NC} 2/45"
echo -e "${BOLD}Taux de rendement   :${NC} 98%"
echo -e "${BOLD}Liaison Centrale    :${NC} $IP_ADDR"
echo ""
echo "=========================================================="
echo ""
echo -e "${GREEN}"

echo "          [Entrer pour ouvrir le sas terminal]"
read -p ""
