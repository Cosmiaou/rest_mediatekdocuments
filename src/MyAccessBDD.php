<?php
include_once("AccessBDD.php");

/**
 * Classe de construction des requêtes SQL
 * hérite de AccessBDD qui contient les requêtes de base
 * Pour ajouter une requête :
 * - créer la fonction qui crée une requête (prendre modèle sur les fonctions 
 *   existantes qui ne commencent pas par 'traitement')
 * - ajouter un 'case' dans un des switch des fonctions redéfinies 
 * - appeler la nouvelle fonction dans ce 'case'
 */
class MyAccessBDD extends AccessBDD {
	    
    /**
     * constructeur qui appelle celui de la classe mère
     */
    public function __construct(){
        try{
            parent::__construct();
        }catch(\Exception $e){
            throw $e;
        }
    }

    /**
     * demande de recherche
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return array|null tuples du résultat de la requête ou null si erreur
     * @override
     */	
    protected function traitementSelect(string $table, ?array $champs) : ?array{
        switch($table){  
            case "livre" :
                return $this->selectAllLivres($champs);
            case "dvd" :
                return $this->selectAllDvd($champs);
            case "revue" :
                return $this->selectAllRevues($champs);
            case "exemplaire" :
                return $this->selectExemplairesRevue($champs);
            case "commande_revue":
                return $this->selectCommandeRevue($champs, false);
            case "abonnements":
                return $this->selectCommandeRevue($champs, true);
            case "commande_livre":
            case "commande_dvd":
                return $this->selectCommandeDocuments($champs);
            case "utilisateur_check":
                return $this->checkUtilisateur($champs);
            case "utilisateur":
                return null;
            case "genre" :
            case "public" :
            case "suivi":
            case "rayon" :
            case "service":
            case "etat" :
                // select portant sur une table contenant juste id et libelle
                return $this->selectTableSimple($table);
            case "" :
                // return $this->uneFonction(parametres);
            default:
                // cas général
                return $this->selectTuplesOneTable($table, $champs);
        }	
    }

    /**
     * demande d'ajout (insert)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples ajoutés ou null si erreur
     * @override
     */	
    protected function traitementInsert(string $table, ?array $champs) : ?int{
        switch($table){
            case "livre":
                return $this->insertTupleLivre($champs);
            case "dvd":
               return $this->insertTupleDvd($champs);
            case "revue":
               return $this->insertTupleRevue($champs);
            case "commande":
                return $this->insertCommandeDocuments($champs);
            case "abonnement":
                return $this->insertAbonnement($champs);
            case "" :
                // return $this->uneFonction(parametres);
            default:
                // cas général
                return $this->insertOneTupleOneTable($table, $champs);	
        }
    }
    
    /**
     * demande de modification (update)
     * @param string $table
     * @param string|null $id
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples modifiés ou null si erreur
     * @override
     */	
    protected function traitementUpdate(string $table, ?string $id, ?array $champs) : ?int{
        switch($table){
            case "livre":
                return $this->updateTupleLivre($champs);
            case "dvd":
                return $this->updateTupleDvd($champs);
            case "revue":
                return $this->updateTupleRevue($champs);
            case "commandedocument":
                return $this->updateCommandeDocument($id, $champs);
            case "" :
                // return $this->uneFonction(parametres);
            default:                    
                // cas général
                return $this->updateOneTupleOneTable($table, $id, $champs);
        }	
    }  
    
    /**
     * demande de suppression (delete)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples supprimés ou null si erreur
     * @override
     */	
    protected function traitementDelete(string $table, ?array $champs) : ?int{
        switch($table){
            case "livre":
                return $this->deleteTuplesDocument($table, $champs);
            case "dvd":
                return $this->deleteTuplesDocument($table, $champs);
            case "revue":
                return $this->deleteTuplesDocument($table, $champs);
            case "" :
                // return $this->uneFonction(parametres);
            default:
                // cas général
                return $this->deleteTuplesOneTable($table, $champs);	
        }
    }	    
        
    /**
     * récupère les tuples d'une seule table
     * @param string $table
     * @param array|null $champs
     * @return array|null 
     */
    private function selectTuplesOneTable(string $table, ?array $champs) : ?array{
        if(empty($champs)){
            // tous les tuples d'une table
            $requete = "select * from $table;";
            return $this->conn->queryBDD($requete);  
        }else{
            // tuples spécifiques d'une table
            $requete = "select * from $table where ";
            foreach ($champs as $key => $value){
                $requete .= "$key=:$key and ";
            }
            // (enlève le dernier and)
            $requete = substr($requete, 0, strlen($requete)-5);	          
            return $this->conn->queryBDD($requete, $champs);
        }
    }	

    /**
     * demande d'ajout (insert) d'un tuple dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples ajoutés (0 ou 1) ou null si erreur
     */	
    private function insertOneTupleOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "insert into $table (";
        foreach ($champs as $key => $value){
            $requete .= "$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ") values (";
        foreach ($champs as $key => $value){
            $requete .= ":$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ");";
        return $this->conn->updateBDD($requete, $champs);
    }
    
    /**
     * Insère un tuple dans document et un dans livre.
     * En cas de problème, rollback, sinon commit
     * @param array|null $champs
     * @return int|null nombre de tuples ajoutés (0 ou 1) ou null si erreur
     */
    private function insertTupleLivre(?array $champs) : ?int{
        $doc = [
            'id' => $champs['Id'],
            'titre' => $champs['Titre'] ?? null,
            'image' => $champs['Image'] ?? null,
            'idRayon' => $champs['IdRayon'] ?? null,
            'idPublic' => $champs['IdPublic'] ?? null,
            'idGenre' => $champs['IdGenre'] ?? null
        ];
        $livre = [
            'id' => $champs['Id'],
            'ISBN' => $champs['ISBN'] ?? null,
            'auteur' => $champs['Auteur'] ?? null,
            'collection' => $champs['Collection'] ?? null
        ];
        $doc = array_filter($doc, fn($v) => $v !== null);
        $livre = array_filter($livre, fn($v) => $v !== null);
        
        try {
            $this->conn->beginTransaction();
            $nb = 0;
            $nb += $this->insertOneTupleOneTable("document", $doc);
            $nb += $this->insertOneTupleOneTable("livres_dvd", ['id' => $champs['Id']]);
            $nb += $this->insertOneTupleOneTable("livre", $livre);
            $this->conn->commit();
            return $nb;
        } catch (Exception $ex) {
            $this->conn->rollBack();
            return null;
        }
    }
    
    /**
     * Insère un tuple dans document et un dans dvd.
     * En cas de problème, rollback, sinon commit
     * @param array|null $champs
     * @return int|null nombre de tuples ajoutés (0 ou 1) ou null si erreur
     */
    private function insertTupleDvd(?array $champs) : ?int{
        $doc = [
            'id' => $champs['Id'],
            'titre' => $champs['Titre'] ?? null,
            'image' => $champs['Image'] ?? null,
            'idRayon' => $champs['IdRayon'] ?? null,
            'idPublic' => $champs['IdPublic'] ?? null,
            'idGenre' => $champs['IdGenre'] ?? null
        ];
        $dvd = [
            'id' => $champs['Id'],
            'synopsis' => $champs['Synopsis'] ?? null,
            'realisateur' => $champs['Realisateur'] ?? null,
            'duree' => $champs['Duree'] ?? null
        ];
        $doc = array_filter($doc, fn($v) => $v !== null);
        $dvd = array_filter($dvd, fn($v) => $v !== null);
        
        try {
            $this->conn->beginTransaction();
            $nb = 0;
            $nb += $this->insertOneTupleOneTable("document", $doc);
            $nb += $this->insertOneTupleOneTable("livres_dvd", ['id' => $champs['Id']]);
            $nb += $this->insertOneTupleOneTable("dvd", $dvd);
            $this->conn->commit();
            return $nb;
        } catch (Exception $ex) {
            $this->conn->rollBack();
            return null;
        }
    }
    
    /**
     * Insère un tuple dans document et un dans revue.
     * En cas de problème, rollback, sinon commit
     * @param array|null $champs
     * @return int|null nombre de tuples ajoutés (0 ou 1) ou null si erreur
     */
    private function insertTupleRevue(?array $champs) : ?int{
        $doc = [
            'id' => $champs['Id'],
            'titre' => $champs['Titre'] ?? null,
            'image' => $champs['Image'] ?? null,
            'idRayon' => $champs['IdRayon'] ?? null,
            'idPublic' => $champs['IdPublic'] ?? null,
            'idGenre' => $champs['IdGenre'] ?? null
        ];
        $revue = [
            'id' => $champs['Id'],
            'periodicite' => $champs['Periodicite'] ?? null,
            'delaiMiseADispo' => $champs['DelaiMiseADispo'] ?? null
        ];
        $doc = array_filter($doc, fn($v) => $v !== null);
        $revue = array_filter($revue, fn($v) => $v !== null);
        
        try {
            $this->conn->beginTransaction();
            $nb = 0;
            $nb += $this->insertOneTupleOneTable("document", $doc);
            $nb += $this->insertOneTupleOneTable("revue", $revue);
            $this->conn->commit();
            return $nb;
        } catch (Exception $ex) {
            $this->conn->rollBack();
            return null;
        }
    }


    /**
     * demande de modification (update) d'un tuple dans une table
     * @param string $table
     * @param string\null $id
     * @param array|null $champs 
     * @return int|null nombre de tuples modifiés (0 ou 1) ou null si erreur
     */	
    private function updateOneTupleOneTable(string $table, ?string $id, ?array $champs) : ?int {
        if(empty($champs)){
            return null;
        }
        if(is_null($id)){
            return null;
        }
        // construction de la requête
        $requete = "update $table set ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);				
        $champs["id"] = $id;
        $requete .= " where id=:id;";		
        return $this->conn->updateBDD($requete, $champs);	        
    }
    
    /**
     * Met à jour l'idSuivi d'une commande de document
     * @param string|null $id
     * @param array|null $champs
     * @return int|null nombre de tuples ajoutés (0 ou 1) ou null si erreur
     */
    private function updateCommandeDocument(?string $id, ?array $champs) : ?int{
        $param = [
            'idSuivi' => $champs['IdSuivi']
        ];
        return $this->updateOneTupleOneTable("commandedocument", $id, $param);
    }
    
    /**
     * Crée une transaction et appelle updateOneTupleOneTable pour la table livre et la table "document".
     * En cas de problème, rollback, sinon commit
     * @param string $table
     * @param string|null $id
     * @param array|null $champs
     * @return int|null nombre de lignes modifiées
     */
    private function updateTupleLivre(?array $champs) : ?int {
        $doc = [
            'id' => $champs['Id'],
            'titre' => $champs['Titre'] ?? null,
            'image' => $champs['Image'] ?? null,
            'idRayon' => $champs['IdRayon'] ?? null,
            'idPublic' => $champs['IdPublic'] ?? null,
            'idGenre' => $champs['IdGenre'] ?? null
        ];
        $livre = [
            'id' => $champs['Id'],
            'ISBN' => $champs['ISBN'] ?? null,
            'auteur' => $champs['Auteur'] ?? null,
            'collection' => $champs['Collection'] ?? null
        ];
        $doc = array_filter($doc, fn($v) => $v !== null);
        $livre = array_filter($livre, fn($v) => $v !== null);
        
        try {
            $this->conn->beginTransaction();
            $nb = 0;
            $nb += $this->updateOneTupleOneTable("document", $champs['Id'], $doc);
            $nb += $this->updateOneTupleOneTable("livre", $champs['Id'], $livre);
            $this->conn->commit();
            return $nb;
        } catch (Exception $ex) {
            $this->conn->rollBack();
            return null;
        }
    }
    
    /**
     * Crée une transaction et appelle updateOneTupleOneTable pour la table dvd et la table "document".
     * En cas de problème, rollback, sinon commit
     * @param string $table
     * @param string|null $id
     * @param array|null $champs
     * @return int|null nombre de lignes modifiées
     */
    private function updateTupleDvd(?array $champs) : ?int {
        $doc = [
            'id' => $champs['Id'],
            'titre' => $champs['Titre'] ?? null,
            'image' => $champs['Image'] ?? null,
            'idRayon' => $champs['IdRayon'] ?? null,
            'idPublic' => $champs['IdPublic'] ?? null,
            'idGenre' => $champs['IdGenre'] ?? null
        ];
        $dvd = [
            'id' => $champs['Id'],
            'synopsis' => $champs['Synopsis'] ?? null,
            'realisateur' => $champs['Realisateur'] ?? null,
            'duree' => $champs['Duree'] ?? null
        ];
        $doc = array_filter($doc, fn($v) => $v !== null);
        $dvd = array_filter($dvd, fn($v) => $v !== null);
        
        try {
            $this->conn->beginTransaction();
            $nb = 0;
            $nb += $this->updateOneTupleOneTable("document", $champs['Id'], $doc);
            $nb += $this->updateOneTupleOneTable("dvd", $champs['Id'], $dvd);
            $this->conn->commit();
            return $nb;
        } catch (Exception $ex) {
            $this->conn->rollBack();
            return null;
        }
    }
    
        /**
     * Crée une transaction et appelle updateOneTupleOneTable pour la table revue et la table "document".
     * En cas de problème, rollback, sinon commit
     * @param string $table
     * @param string|null $id
     * @param array|null $champs
     * @return int|null nombre de lignes modifiées
     */
    private function updateTupleRevue(?array $champs) : ?int {
        $doc = [
            'id' => $champs['Id'],
            'titre' => $champs['Titre'] ?? null,
            'image' => $champs['Image'] ?? null,
            'idRayon' => $champs['IdRayon'] ?? null,
            'idPublic' => $champs['IdPublic'] ?? null,
            'idGenre' => $champs['IdGenre'] ?? null
        ];
        $revue = [
            'id' => $champs['Id'],
            'periodicite' => $champs['Periodicite'] ?? null,
            'delaiMiseADispo' => $champs['DelaiMiseADispo'] ?? null
        ];
        $doc = array_filter($doc, fn($v) => $v !== null);
        $revue = array_filter($revue, fn($v) => $v !== null);
        
        try {
            $this->conn->beginTransaction();
            $nb = 0;
            $nb += $this->updateOneTupleOneTable("document", $champs['Id'], $doc);
            $nb += $this->updateOneTupleOneTable("revue", $champs['Id'], $revue);
            $this->conn->commit();
            return $nb;
        } catch (Exception $ex) {
            $this->conn->rollBack();
            return null;
        }
    }
    
    /**
     * demande de suppression (delete) d'un ou plusieurs tuples dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples supprimés ou null si erreur
     */
    private function deleteTuplesOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "delete from $table where ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key and ";
        }
        // (enlève le dernier and)
        $requete = substr($requete, 0, strlen($requete)-5);   
        return $this->conn->updateBDD($requete, $champs);	        
    }
    
    /**
     * Crée une transaction et appelle deleteTuplesOneTable pour supprimer des tuples en suivant les propriétés ACID
     * En fonction de l'id, supprime depuis la table indiqué, depuis 'livres_dvd" si nécessaire et depuis "document"
     * Ne fonctionne que pour les tables DVD et livre. Ne pas utiliser dans une autre contexte.
     * @param string $table
     * @param array|null $champs
     * @return int|null
     */
    private function deleteTuplesDocument(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        try {
            $this->conn->beginTransaction();
            $nb = 0;
            $nb += $this->deleteTuplesOneTable($table, $champs);
            if($table == "livre" || "dvd"){
                $nb += $this->deleteTuplesOneTable('livres_dvd', $champs);
            }
            $nb += $this->deleteTuplesOneTable('document', $champs);
            $this->conn->commit();
            return $nb;
        } catch (Exception $ex) {
            $this->conn->rollBack();
            return null;
        }
    }
    
    /**
     * récupère toutes les lignes d'une table simple (qui contient juste id et libelle)
     * @param string $table
     * @return array|null
     */
    private function selectTableSimple(string $table) : ?array{
        $requete = "select * from $table order by libelle;";		
        return $this->conn->queryBDD($requete);	    
    }
    
    /**
     * récupère toutes les lignes de la table Livre et les tables associées
     * Champs peut contenir l'id d'un livre, dans ce cas seulemnt lui sera retournée
     * @return array|null
     */
    private function selectAllLivres(?array $champs = null) : ?array{
        $requete = "Select l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from livre l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        if(!empty($champs)) {
            $requete .= "WHERE l.id = :id ";
        }
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete, $champs);
    }	

    /**
     * récupère toutes les lignes de la table DVD et les tables associées
     * Champs peut contenir l'id d'un dvd, dans ce cas seulemnt lui sera retournée
     * @return array|null
     */
    private function selectAllDvd(?array $champs = null) : ?array{
        $requete = "Select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from dvd l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        if(!empty($champs)) {
            $requete .= "WHERE l.id = :id ";
        }
        $requete .= "order by titre ";	
        return $this->conn->queryBDD($requete, $champs);
    }	

    /**
     * récupère toutes les lignes de la table Revue et les tables associées
     * Champs peut contenir l'id d'une revue, dans ce cas seulemnt elle sera retournée
     * @return array|null
     */
    private function selectAllRevues(?array $champs = null) : ?array{
        $requete = "Select l.id, l.periodicite, d.titre, d.image, l.delaiMiseADispo, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from revue l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        if(!empty($champs)) {
            $requete .= "WHERE l.id = :id ";
        }
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete, $champs);
    }	

    /**
     * récupère tous les exemplaires d'une revue
     * @param array|null $champs 
     * @return array|null
     */
    private function selectExemplairesRevue(?array $champs) : ?array{
        if(empty($champs)){
            return null;
        }
        if(!array_key_exists('id', $champs)){
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "Select e.id, e.numero, e.dateAchat, e.photo, e.idEtat ";
        $requete .= "from exemplaire e join document d on e.id=d.id ";
        $requete .= "where e.id = :id ";
        $requete .= "order by e.dateAchat DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }
    
    /**
     * Récupère toutes les commandes d'un document Livre ou Dvd en fonction d'un id
     */
    private function selectCommandeDocuments(?array $champs)
    {
        $requete = "SELECT cd.id, cd.nbExemplaire, cd.idSuivi, cd.idLivreDvd, s.libelle as suivi, c.dateCommande, c.montant ";
        $requete .= "FROM commandedocument as cd ";
        $requete .= "JOIN commande as c ON cd.id = c.id ";
        $requete .= "JOIN suivi as s ON cd.idSuivi = s.id ";
        $requete .= "WHERE cd.idLivreDvd = :id";
        return $this->conn->queryBDD($requete, $champs);
    }
    
    /**
     * Retourne les informations d'un abonnement
     * Si $demandeExpiration est VRAI, retourne uniquement ceux dont la date d'expiration est dans moins de 30 jours.
     * S'il est faux, retourne uniquement ceux dont l'Id de la revue associée corresponds à celui indiqué.
     * @param array|null $champs
     * @param bool $demandeExpiration
     * @return type
     */
    private function selectCommandeRevue(?array $champs, bool $demandeExpiration)
    {
        $requete = "SELECT a.id, a.dateFinAbonnement, a.idRevue, c.dateCommande, c.montant ";
        $requete .= "FROM abonnement a ";
        $requete .= "JOIN commande c ON c.id = a.id ";
        if(!$demandeExpiration) {
            $requete .= "WHERE a.idRevue = :id ";
        } else {
            $requete .= "WHERE DATEDIFF(a.dateFinAbonnement, now()) < 30 ";
        }
        $requete .= "ORDER BY c.dateCommande DESC;";
        return $this->conn->queryBDD($requete, $champs);
    }
    
    /**
     * Insère des éléments dans les table commande et commandedocument
     * Doit recevoir l'id, la date de commande, le montant, le nombre d'exemplaire, l'idSuivi et l'id du document
     * @param array|null $champs
     * @return int|null
     */
    private function insertCommandeDocuments(?array $champs) : ?int
    {
        if(empty($champs)){ return null; }
        
        $commande = [
            'id' => $champs['Id'],
            'dateCommande' => $champs['DateCommande'],
            'montant' => $champs['Montant'] ?? null
        ];
        $commandeDocument = [
            'id' => $champs['Id'],
            'nbExemplaire' => $champs['NbExemplaire'],
            'idSuivi' => $champs['IdSuivi'],
            'idLivreDvd' => $champs['IdLivreDvd']
        ];
        $commande = array_filter($commande, fn($v) => $v !== null);
        $commandeDocument = array_filter($commandeDocument, fn($v) => $v !== null);
        
        $this->conn->beginTransaction();
        $nb = 0;
        try {
            $nb += $this->insertOneTupleOneTable("commande", $commande);
            $nb += $this->insertOneTupleOneTable("commandedocument", $commandeDocument);
            $this->conn->commit();
        } catch (Exception $ex) {
            $this->conn->rollBack();
        }
        return $nb;
    }
    
    /**
     * Insère un tuple dans commande et dans abonnement.
     * Doit recevoir l'id, la date de commande, le montant, la date de fin et l'id de la revue associée.
     * @param array|null $champs
     * @return int|null
     */
    private function insertAbonnement(?array $champs) : ?int
    {
        if(empty($champs)){ return null; }
        
        $commande = [
            'id' => $champs['Id'],
            'dateCommande' => $champs['DateCommande'],
            'montant' => $champs['Montant'] ?? null
        ];
        $abonnement = [
            'id' => $champs['Id'],
            'dateFinAbonnement' => $champs['DateFinAbonnement'],
            'idRevue' => $champs['IdRevue']
        ];
        $commande = array_filter($commande, fn($v) => $v !== null);
        $abonnement = array_filter($abonnement, fn($v) => $v !== null);
        
        $this->conn->beginTransaction();
        $nb = 0;
        try {
            $nb += $this->insertOneTupleOneTable("commande", $commande);
            $nb += $this->insertOneTupleOneTable("abonnement", $abonnement);
            $this->conn->commit();
        } catch (Exception $ex) {
            $this->conn->rollBack();
        }
        return $nb;
    }
    
    /**
     * Reçoit le nom et le mot de passe d'un compte. Si un compte correspond, retourne son idService
     * @param type $champs
     * @return array|null 
     */
    public function checkUtilisateur($champs) : ?array
    {
        if(empty($champs)){ return null; }
        
        $champsCorrects = [
            'nom' => $champs['Nom'],
            'password' => $champs['Password'] ?? null
        ];
        
        $requete = "SELECT u.idService FROM utilisateur u ";
        $requete .= "WHERE u.nom = :nom AND u.password = :password ";
        $requete .= "LIMIT 1;";
        return $this->conn->queryBDD($requete, $champsCorrects);
    }
    
}