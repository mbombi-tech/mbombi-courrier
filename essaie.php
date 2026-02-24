
<?php

$db_host="localhost";
$db_user="root";
$db_pass="";
$db_name="dbcourriers";

$conn=new mysqli($db_host,$db_user,$db_pass,$db_name);

if($conn->connect_error){
 die("connection failed:".$conn->connect_error);		
}
//fetch conn


	
$sql="select tracking_code ,Type,Objet,expediteur,date_creation  from courriers ";
$result=$conn->query($sql);  

  
?>
		 


<!DOCTYPE html>
<html>
    <head>
	<meta charset="UTF-8">
	<met  a http-equiv="X-UA-compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width", "initial-scale=1.0" >
	
	<title>Download</title> 
	
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
 <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>    
  <link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.bootstrap4.min.css"
  
  </head>

<body>
<nav class="navbar">
  <ul class="menu">
    <li><a href="Page d'accueil.php">Page d'accueil</a></li>

    <li class="dropdown">
      <a href="#" class="dropbtn">Voir les enregistrements ▾</a>
      <div class="dropdown-content">
        <a href="vuep.php">Vue principale </a>
        <a href="identité.php">Identité</a>
        <a href="carrière.php">Carrière</a>
      </div>
    </li>

    <li><a href="J.php">Aller au Formulaire</a></li>

    <li class="dropdown" >
      <a href="#" class="dropbtn" ">Statistique▾&nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp</a>
      <div class="dropdown-content">
        <a href="statistiques.php">Grade/sexe</a>
        <a href="tranchedage.php">Tranche d'age/sexe</a>
      </div>
    </li>
	<li><a href="Page d'accueil.php">Imprimer</a></li>
  </ul>
</nav>

<style>
  .navbar {
    background-color: #0d3b66;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    position: sticky;
    top: 0;
    width: 100%;
    z-index: 1000;
    display: flex;
    justify-content: center; /* Centrage horizontal */
v   }

  .menu {
    list-style-type: none;
    margin: 0;
    padding: 0;
    display: flex;
    align-items: center;
    height: 50px;
  }

  .menu > li {
    position: relative;
  }

  .menu > li > a,
  .dropbtn {
    display: block;
    color: white;
    text-decoration: none;
    padding: 14px 20px;
    font-weight: 600;
    letter-spacing: 0.03em;
    transition: background-color 0.3s ease;
  }

  .menu > li > a:hover,
  .dropdown:hover > .dropbtn {
    background-color: #1e6091;
    cursor: pointer;
  }

  .dropdown-content {
    display: none;
    position: absolute;
    background-color: #144d77;
    min-width: 220px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
    top: 50px;
    left: 0;
    border-radius: 0 0 8px 8px;
  }

  .dropdown-content a {
    color: white;
    padding: 12px 18px;
    text-decoration: none;
    display: block;
    font-weight: 500;
    transition: background-color 0.3s ease;
  }

  .dropdown-content a:hover {
    background-color: #1e6091;
  }

  .dropdown:hover .dropdown-content {
    display: block;
  }
</style>

<div class="vertical-menu">


  <div>
    <a class="active" href="vuep.php" style="display: inline-block; 
	margin-bottom: 15px; color:white; font-weight: bold; text-decoration: none;">← Vue principale</a>
  </div>

  <ul class="menu2">
    <li><a href="#" >Listes criterisées</a>
      <ul class="submenu2">
        <li><a href="listeh.php">Liste des hommes</a></li>
        <li><a href="listef.php">Liste des femmes</a></li>
        <li><a href="#">Personnel intérieur</a>
          <ul class="submenu2">
		    <li><a href="#">✦✦Total(Pers Int)</a></li>
            <li><a href="#">✦✦Hommes(Pers Int)</a></li>
            <li><a href="#">✦✦Femmes(Pers Int)</a></li>
          </ul>
        </li>
        <li><a href="#">Personnel extérieur</a>
          <ul class="submenu2">
		    <li><a href="#">✦✦Total(Pers Exp)</a></li>
            <li><a href="#">✦✦Hommes(Pers Exp)</a></li>
            <li><a href="#">✦✦Femmes(Pers Exp)</a></li>
          </ul>
        </li>
        <li><a href="#">Liste par Direction</a>
          <ul class="submenu2">
            <li><a href="#">✦✦Academie diplomatique</a></li>
			<li><a href="#">✦✦Afrique</a></li>
            <li><a href="#">✦✦Amerique</a></li>
            <li><a href="#">✦✦Asie</a></li>
			<li><a href="#">✦✦Chancellerie</a></li>
			<li><a href="#">✦✦Congolais de l'Etranger</a></li>
			<li><a href="#">✦✦DAF</a></li>
            <li><a href="#">✦✦DANTIC</a></li>
            <li><a href="#">✦✦DEP</a></li>
            <li><a href="#">✦✦DRH</a></li>
            <li><a href="#">✦✦Europe</a></li>
			<li><a href="#">✦✦Francophonie</a></li>
            <li><a href="#">✦✦Juridique</a></li>
			<li><a href="#">✦✦Organisation internationale</a></li>
			<li><a href="#">✦✦Protocole d'Etat</a></li>
            <li><a href="#">✦✦Secretariat general</a></li>
            
          </ul>
        </li>
        
      </ul>
    </li>
  </ul>


</div>



<style>
.vertical-menu {
  width: 250px;
  position: absolute;
  top: 70px;
  left: 20px;
  background-color: #0d3b66;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  z-index: 1050; /* 👈 pour qu’elle soit au-dessus */
}


.vertical-menu a,
.vertical-menu .menu-toggle {
  display: block;
  color: white;
  padding: 14px 20px;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.3s ease;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  text-align: left;
  background: none;
  border: none;
  width: 100%;
  text-align: left;
}

.vertical-menu a:hover,
.vertical-menu .menu-toggle:hover {
  background-color: #1e6091;
  padding-left: 26px;
  cursor: pointer;
}

.vertical-menu a.active {
  background-color: #1e6091;
  color: #fff;
  font-weight: 600;
  border-left: 4px solid skyblue;
  padding-left: 22px;
}

.vertical-menu .collapse a {
  padding-left: 32px;
  font-size: 14px;
}
.vertical-menu .collapse {
  position: relative;
  z-index: 1060; /* 👈 encore plus élevé pour le contenu */
  background-color: #144d77;
}

 .body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 20px;
      background-color: #f5f5f5;
    }

    /* Menu principal */
    .menu2 {
      width: 260px;
      background-color: #0d3b66;
      list-style: none;
      padding: 0;
      margin: 0;
      border-radius: 8px;
      overflow: hidden;
    }

    .menu2 li {
      position: relative;
    }

    .menu2 li a {
      display: block;
      padding: 12px 20px;
      color: white;
      text-decoration: none;
      transition: background-color 0.3s ease;
    }

    .menu2 li a:hover {
      background-color: #1e6091;
    }

    /* Sous-menus */
    .submenu2 {
      display: none;
      list-style: none;
      padding: 0;
      margin: 0;
      background-color: #124f79;
    }

    .submenu2 .submenu2 {
      background-color: #175b88;
    }

    /* Flèches indicatrices */
    .menu2 li a:after {
      content: "▼";
      float: right;
      font-size: 12px;
      margin-left: 10px;
    }

    .menu2 li:not(:has(ul)) > a:after {
      content: "";
    }

    /* Affichage du sous-menu au survol */
    li:hover > .submenu2 {
      display: block;
    }

    /* Responsive */
    @media (max-width: 600px) {
      .menu {
        width: 100%;
      }
    }
</style>
<style>
.submenu-wrapper {
  position: relative;
}

.has-submenu::after {
  content: '';
  float: right;
}

.submenu {
  display: none;
  position: absolute;
  left: 100%;
  top: 0;
  background-color: #144d77;
  border-radius: 0 6px 6px 0;
  min-width: 160px;
  z-index: 1070;
  box-shadow: 2px 2px 8px rgba(0,0,0,0.2);
}

.submenu a {
  padding-left: 20px;
  font-size: 14px;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  white-space: nowrap;
}

.submenu-wrapper:hover .submenu {
  display: block;
}

.submenu a:hover {
  background-color: #1e6091;
  padding-left: 26px;
}


</style>

<style>
table.tableau {
  width: auto;
  max-width: 100%;
  margin-left: 100px;
  border-collapse: collapse;
  font-size: 12px;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  text-align: center;
  background-color: #ffffff;
}

table.tableau th {
  background-color: #0d3b66; /* ✅ même que navbar */
  color: white;              /* ✅ texte blanc */
  font-weight: 600;
  letter-spacing:0.02em;
  padding: 4px 1px;
  white-space: normal;
  word-wrap: break-word;
  text-align:center;
}

table.tableau td {
  border: 1px solid #1e6091;
  padding: 4px 1px;
  vertical-align: middle;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Colonnes avec retour à la ligne : Date de naissance (5) & Filières (8) */
table.tableau td:nth-child(5),
table.tableau td:nth-child(8) {
  white-space: normal;
  word-wrap: break-word;
  text-overflow: clip;
}

/* Lignes paires avec fond clair */
table.tableau tbody tr:nth-child(even) {
  background-color: #f9fcfe;
}

/* Hover léger */
table.tableau tbody tr:hover {
  background-color: #e1effa;
  cursor: default;
}

/* Boutons Modifier / Supprimer */
table.tableau a.btn {
  font-size: 11px;
  padding: 3px 6px;
  border-radius: 4px;
  text-decoration: none;
  color: #fff;
  display: inline-block;
  transition: background-color 0.3s ease;
  white-space: nowrap;
}

table.tableau a.btn-primary {
  background-color: #1e6091;
  border: none;
}

table.tableau a.btn-primary:hover {
  background-color: #145374;
}

table.tableau a.btn-danger {
  background-color: #c0392b;
  border: none;
}

table.tableau a.btn-danger:hover {
  background-color: #922b21;
}
</style>


<div class="container mt-5">


<div class="card shadow-sm mb-4" style="border-left: 5px solid #0d3b66; background-color: #f8f9fa;">
  <div class="card-body text-center">
    <h3 class="mb-0" style="color: #0d3b66; font-weight: 700;">Liste des Cadres et Agents</h3>
  </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 px-2" style="flex-wrap: wrap;">
 

  <div>
    <a href="j2.php" class="btn btn-success btn-sm">
      + Ajouter agent
    </a>
  </div>
</div>

<br/>
<table class="tableau table-bordered table-striped" Id="dataTableid" style="position:relative;
text-align:center;margin-left:-8%;font-size:12px " >
 
<thead>
<tr>
<th>N°</th>
<th>Matr</th>
<th>Nom </th>
<th>Post-Nom</th>
<th>Prenom</th>
<th>Date de naissance</th>
<th>Sexe</th>
<th>Niveau d'études</th>
<th>Filières</th>
<th>Année d'ontention dip</th>
<th>Date d'adm sous stat </th>
<th>Grade statutaire </th>
<th>Grade actuel</th>
<th>Direction</th>
<th>Province</th>
<th>Opération1</th>
<th>Opération2</th>
<th>Opération3</th>
<th>Opération4</th>
<th>Opération5</th>
</tr>
</thead>
<tbody>
	
<?php
if($result->num_rows>0) {

while($row=$result->fetch_assoc()){	

 ?>
<tr>

                 <td><?php echo $row['tracking_code ']; ?></td>
                 <td><?php echo $row['Type']; ?></td>
                 <td><?php echo $row['Objet']; ?></td>
                 <td><?php echo $row['expediteur']; ?></td>
                 <td><?php echo $row['Service actuel']; ?></td>
                 <td><?php echo $row['date_creation']; ?> </td>


 <td><a class="nav-link btn btn-primary" href="modifier3.php?Id=<?php echo $row['Id']?>">
 <span class=" oi oi-pencil" aria-hidden="true">Modifier</span>
</a></td>
 <td>
   <a class="nav-link btn btn-primary" href="ajouter.php?Id=<?php echo $row['Id']?>">
   <span class="oi oi-pencil" aria-hidden="true">Aouter documents</span>
  </a>
  </a></td>
 
 <td>
   <a class="nav-link btn btn-primary" href="Download.php?Id=<?php echo $row['Id']?>">
   <span class="oi oi-pencil" aria-hidden="true">voir documents</span>
  </a>
  </a></td>
 
 
<td><a class="nav-link btn btn-primary" href="ajouterDoc.php?Id=<?php echo $row['Id']?>">
 <span class=" oi oi-pencil" aria-hidden="true">Fiche de l'agent</span></td> 
 <td>
   <a class="nav-link btn btn-danger" href="Supprimer.php?Id=<?php echo $row['Id']?>">
   <span class="oi oi-trash" aria-hidden="true">Supprimer</span>
  </a>
 </td>  
 
</tr>
<?php
}	
}

?>	

</tbody>

</table>

</div>



<script src="https://cdn.datatables.net/2.3.2/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.3.2/js/dataTables.bootstrap4.min.js"></script> 

<script> new DataTable('#dataTableid');</script> 


</body>
</html> 