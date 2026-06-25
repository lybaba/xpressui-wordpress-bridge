import csv
import os
import time
import requests

# ==========================================
# CONFIGURATION
# ==========================================
RESEND_API_KEY = "re_your_api_key_here"  # Remplacez par votre clé API Resend
SENDER_EMAIL = "Votre Nom <contact@votredomaine.com>"  # Doit être un domaine validé sur Resend
CSV_FILE = "leads_agences_lyon.csv"  # Le fichier CSV généré par le scraper
DELAY_BETWEEN_EMAILS = 2  # Délai en secondes pour éviter d'envoyer trop vite (limites API)

# ==========================================
# GABARIT DE L'EMAIL (HTML)
# ==========================================
EMAIL_SUBJECT = "Rendre le client intake fluide sur WordPress (sans iframe payante)"

def get_email_body(agency_name):
    return f"""
    <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333333;">
            <p>Bonjour l'équipe de <strong>{agency_name}</strong>,</p>
            
            <p>Je me permets de vous contacter car vous concevez des sites WordPress et accompagnez probablement vos clients dans la collecte de documents ou de formulaires d'onboarding (client intake).</p>
            
            <p>En travaillant avec des agences, j'ai remarqué que beaucoup d'entre elles font face à trois contraintes avec les formulaires traditionnels :</p>
            <ul>
                <li>Les intégrations d'outils SaaS (Typeform, Jotform) via des <strong>iframes</strong> qui s'adaptent mal au mobile.</li>
                <li>Le fait de devoir payer des abonnements "Team" onéreux uniquement pour que leurs clients ou collaborateurs puissent assigner et traiter les soumissions en équipe.</li>
                <li>Les complications liées au <strong>RGPD</strong> lorsque des pièces d'identité ou justificatifs clients transitent sur des serveurs tiers.</li>
            </ul>
            
            <p>Pour résoudre cela, nous avons développé <strong>IntakeFlow</strong>, un plugin WordPress gratuit et open-source. Il permet de :</p>
            <ol>
                <li>Afficher des formulaires multi-étapes <strong>niveaux thèmes (sans iframe)</strong> en HTML/JS pur.</li>
                <li>Gérer, attribuer et suivre le statut des dossiers clients via un <strong>inbox collaboratif gratuit intégré dans wp-admin</strong>.</li>
                <li>Stocker les fichiers de manière 100% sécurisée dans <strong>votre propre base de données WordPress</strong>.</li>
            </ol>
            
            <p>Le plugin dispose également d'un importateur en 1 clic pour convertir instantanément vos formulaires existants depuis Contact Form 7 ou Gravity Forms.</p>
            
            <p>Seriez-vous ouverts à y jeter un œil pour vos futurs projets clients ? La version de base est totalement autonome et gratuite.</p>
            
            <p>Vous pouvez découvrir le projet ici : <a href="https://wordpress.org/plugins/xpressui-bridge/">Fiche WordPress.org</a> ou sur notre site <a href="https://intakeflow.dev/">intakeflow.dev</a>.</p>
            
            <p>Bonne journée,<br>
            <strong>L'équipe IntakeFlow</strong></p>
        </body>
    </html>
    """

# ==========================================
# ENVOI DES MAILS
# ==========================================
def send_email_via_resend(to_email, agency_name):
    url = "https://api.resend.com/emails"
    headers = {
        "Authorization": f"Bearer {RESEND_API_KEY}",
        "Content-Type": "application/json"
    }
    
    payload = {
        "from": SENDER_EMAIL,
        "to": [to_email],
        "subject": EMAIL_SUBJECT,
        "html": get_email_body(agency_name)
    }
    
    try:
        response = requests.post(url, headers=headers, json=payload)
        if response.status_code == 200 or response.status_code == 201:
            print(f"✅ E-mail envoyé avec succès à {agency_name} ({to_email})")
            return True
        else:
            print(f"❌ Échec de l'envoi pour {agency_name}. Code erreur: {response.status_code}. Message: {response.text}")
            return False
    except Exception as e:
        print(f"❌ Erreur réseau lors de l'envoi à {agency_name}: {e}")
        return False

def process_outreach():
    if not os.path.exists(CSV_FILE):
        print(f"Erreur : Le fichier CSV '{CSV_FILE}' n'existe pas. Veuillez d'abord lancer agency_finder.py.")
        return
        
    print(f"Démarrage de la campagne d'e-mailing avec le fichier : {CSV_FILE}")
    
    # Pour éviter d'envoyer deux fois au même email
    sent_log_file = "sent_emails_log.txt"
    sent_emails = set()
    if os.path.exists(sent_log_file):
        with open(sent_log_file, "r") as log:
            sent_emails = set(line.strip() for line in log)

    with open(CSV_FILE, mode='r', encoding='utf-8-sig') as file:
        reader = csv.DictReader(file)
        
        for row in reader:
            agency_name = row.get('Nom')
            email = row.get('Email')
            
            if not email or email == "Non renseigné" or "@" not in email:
                print(f"⚠️ Ignoré : {agency_name} n'a pas d'adresse e-mail valide renseignée dans le CSV.")
                continue
                
            if email in sent_emails:
                print(f"⏭️ Déjà envoyé : {agency_name} ({email}) a déjà reçu un e-mail dans cette campagne.")
                continue
            
            # Envoi du mail
            success = send_email_via_resend(email, agency_name)
            
            if success:
                # Log de l'envoi réussi
                with open(sent_log_file, "a") as log:
                    log.write(f"{email}\n")
                
                # Attente pour respecter les limites d'envoi
                time.sleep(DELAY_BETWEEN_EMAILS)

if __name__ == "__main__":
    # AVERTISSEMENT DE SÉCURITÉ : Ne lancez pas sans avoir configuré vos clés API ci-dessus
    if RESEND_API_KEY == "re_your_api_key_here":
        print("🛑 VEUILLEZ CONFIGURER VOTRE CLÉ API RESEND ET VOTRE ADRESSE D'EXPÉDITEUR AVANT DE LANCER LE SCRIPT.")
    else:
        process_outreach()
