<?php $cheminDoc = null;

if (!empty($_FILES['document']['name'])) {

    $dossier = "../uploads/courriers/";
    if (!is_dir($dossier)) {
        mkdir($dossier, 0777, true);
    }

    $nomFichier = time() . "_" . basename($_FILES['document']['name']);
    $cheminDoc = $dossier . $nomFichier;

    move_uploaded_file($_FILES['document']['tmp_name'], $cheminDoc);
}
 ?>