#!/bin/bash

# --- Couleurs et Styles ---
BOLD="\033[1m"
CYAN="\033[36m"
GREEN="\033[32m"
RED="\033[31m"
YELLOW="\033[33m"
RESET="\033[0m"

# --- Interface ---
clear
echo -e "${BOLD}${CYAN}=============================================="
echo -e "   LXC MULTI-COMMAND EXECUTOR"
echo -e "==============================================${RESET}"
echo ""

# Demande de la commande
echo -e "${YELLOW}# Entrez la commande à envoyer :${RESET}"
read -p " > " commande

if [ -z "$commande" ]; then
    echo -e "${RED}Erreur : Aucune commande saisie. Arrêt.${RESET}"
    exit 1
fi

echo -e "\n${BOLD}Lancement sur les conteneurs actifs...${RESET}\n"

# --- Boucle d'exécution ---
# On utilise --running pour éviter les erreurs sur les conteneurs éteints
for el in $(lxc-ls --running); do
    echo -e "${CYAN}┌───[ ${BOLD}${el}${RESET}${CYAN} ]${RESET}"
    
    # Exécution de la commande
    # On capture la sortie pour pouvoir l'indenter légèrement (plus esthétique)
    output=$(lxc-attach -n "$el" -- bash -c "$commande" 2>&1)
    
    if [ $? -eq 0 ]; then
        # Affichage du résultat avec une petite flèche
        echo -e "${GREEN}│${RESET} ${output:-${BOLD}Commande exécutée avec succès (sans retour)${RESET}}"
    else
        echo -e "${RED}│ ERROR:${RESET} ${output}"
    fi
    
    echo -e "${CYAN}└──────────────────────────────────────────────${RESET}\n"
done

echo -e "${BOLD}${GREEN}Terminé !${RESET}"
