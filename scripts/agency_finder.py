import requests
import csv
import os

def fetch_agencies(city_name="Paris"):
    print(f"Recherche des agences et entreprises IT à : {city_name}...")
    
    # Endpoint de l'API publique Overpass d'OpenStreetMap
    overpass_url = "https://overpass-api.de/api/interpreter"
    
    # Requête Overpass QL pour chercher les bureaux IT, Web et Publicitaires
    overpass_query = f"""
    [out:json][timeout:25];
    area["name"="{city_name}"]->.searchArea;
    (
      node["office"="it"](area.searchArea);
      way["office"="it"](area.searchArea);
      node["office"="advertising"](area.searchArea);
      way["office"="advertising"](area.searchArea);
    );
    out body;
    >;
    out skel qt;
    """
    
    try:
        response = requests.post(overpass_url, data={'data': overpass_query})
        response.raise_for_status()
        data = response.json()
    except Exception as e:
        print(f"Erreur lors de la requête API : {e}")
        return []

    elements = data.get('elements', [])
    leads = []
    
    for element in elements:
        tags = element.get('tags', {})
        # On ne garde que les éléments qui ont au moins un nom
        if 'name' in tags:
            name = tags.get('name')
            website = tags.get('website', tags.get('contact:website', 'Non renseigné'))
            email = tags.get('email', tags.get('contact:email', 'Non renseigné'))
            phone = tags.get('phone', tags.get('contact:phone', 'Non renseigné'))
            street = tags.get('addr:street', '')
            city = tags.get('addr:city', city_name)
            postcode = tags.get('addr:postcode', '')
            
            address = f"{street} {postcode} {city}".strip()
            if not address:
                address = "Non renseignée"
                
            leads.append({
                'Nom': name,
                'Site Web': website,
                'Email': email,
                'Téléphone': phone,
                'Adresse': address,
                'Type': tags.get('office', 'Entreprise')
            })
            
    return leads

def save_to_csv(leads, filename="leads_agences.csv"):
    if not leads:
        print("Aucun lead à sauvegarder.")
        return
        
    keys = leads[0].keys()
    try:
        with open(filename, 'w', newline='', encoding='utf-8-sig') as output_file:
            dict_writer = csv.DictWriter(output_file, fieldnames=keys)
            dict_writer.writeheader()
            dict_writer.writerows(leads)
        print(f"Fichier de prospection sauvegardé avec succès ({len(leads)} leads) dans : {filename}")
    except IOError as e:
        print(f"Erreur d'écriture du fichier CSV : {e}")

if __name__ == "__main__":
    # Exemple de recherche sur Lyon (vous pouvez changer la ville ici)
    city = "Lyon"
    leads = fetch_agencies(city)
    save_to_csv(leads, f"leads_agences_{city.lower()}.csv")
