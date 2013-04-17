<?php
/**
 * Created by JetBrains PhpStorm.
 * User: damien
 * Date: 13/04/13
 * Time: 18:46
 * To change this template use File | Settings | File Templates.
 */

define("DEBUG_MODE", TRUE); // bool
define("START_CITY", "A");
define("END_CITY", "F");  

/**
 * fonction utilitaire d'affichage de message de debug, permet de facilement desactiver les messages affichées
 * @param $msg
 */
function _log($msg){
    if(DEBUG_MODE === TRUE){
        echo $msg;
    }
}

class Dijkstra{

    /*déclaration des constantes:*/

    const INFINITE  = -1;

    /*déclaration des propriétes protected*/
    protected $_graph = array();
    protected $_list_city_names = array();
    protected $_list_unvisited_cities = array();
    protected $_matrix;
    protected $_start_city;
    protected $_end_city;

    /**
     * @param $graph array
     */
    public function __construct($graph){
        $this->_graph = $graph;
        $this->_initListCityNamesFromGraph($graph);
        $this->_initGraph($graph);

        $this->_initListVisitedCities();

        _log("<h3 class='primary'>étape 3 : On construit un tableau listant les villes non visitées (par défaut ce tableau contient toutes les villes): </h3>");
        if(DEBUG_MODE){
            _log("<pre>");
            print_r($this->_list_unvisited_cities);
            _log("</pre>");
        }
    }

    /**
     * initialise un tableau contenant simplement la liste des nomes de villes
     * @param $graph array
     */
    protected function _initListCityNamesFromGraph($graph){
        $this->_list_city_names = array_keys($graph);
    }

    /**
     * complète le graph fourni en paramètre pour lui ajouter des distance infinies si necessaire
     * @param $graph array
     */
    protected  function _initGraph($graph){
        foreach($graph as $from => $list_distances){
            foreach($this->_list_city_names as $a_city){
                if(!array_key_exists($a_city, $list_distances)){
                    $graph[$from][$a_city] = self::INFINITE;
                }
            }
        }

        $this->_graph = $graph;

        _log("<h3 class='primary'>étape 1 : On construit un tableau repertoriant pour chaque ville, sa distance par rapport aux autres : </h3>");
        _log("<blockquote class='secondary'>la valeur ".self::INFINITE." indique que la ville n'est pas directement accessible</blockquote>");
        if(DEBUG_MODE){
            _log("<pre>");
            print_r($this->_graph);
            _log("</pre>");
        }
    }

    protected function _initListVisitedCities(){
        //par défaut on considère qu'aucune ville n'a été visitée;
        foreach($this->_list_city_names as $city_name){
            $this->_list_unvisited_cities[] = $city_name;
        }
    }

    /**
     * Initialise la matrice permettant de conserver des informations vis à vis du traversage des villes
     * notamment le 'cost' pour aller à une ville donnée ainsi que le 'previous' permettant de savoir d'où l'on provenait lorsqu'on a visité cette ville
     */
    protected function _initMatrix(){
        $matrix = array();
        foreach($this->_list_city_names as $city){
            $matrix[$city] = array(
                "cost" => self::INFINITE,
                "previous" => null,
            );
            if($city == $this->_start_city){
                $matrix[$city]['cost'] = 0;
            }
        }

        $this->_matrix = $matrix;
    }

    /**
     * recherche la ville ayant le cost le plus faible parmis une liste de nom de ville ( en l'occurence la liste des villes non visités )
     * @return mixed le nom de la ville ayant le cost le plus faible, null si aucune ville n'a été trouvée
     */
    protected function _getCityNameWithMinCost(){

        $city_with_min_cost = array(
            "name" => null,
            "cost" => null,
        );

        $list_invalid_values = array(self::INFINITE, 0);

        foreach($this->_list_unvisited_cities as $unvisited_city_name){
            $city_info = $this->_matrix[$unvisited_city_name];
            if( is_null($city_with_min_cost["cost"])
                OR
                ( !in_array($city_info["cost"], $list_invalid_values) AND $city_info["cost"] < $city_with_min_cost["cost"])
            ){
                $city_with_min_cost['cost'] = $city_info["cost"];
                $city_with_min_cost['name'] = $unvisited_city_name;
            }
        }

        return $city_with_min_cost['name'];
    }

    /**
     * Retourne la liste des villes adjacentes de l'une des villes du graph.
     * les villes considérées comme adjacentes sont celles ayant une distance différente de 0 et de l'infini
     * @param $city string : la ville dont il faut trouver les villes adjacentes
     * @return array
     */
    protected function _getAdjacentCities($city){
        $list_city_infos = $this->_graph[$city];
        $list_adjacent_cities = array();
        foreach($list_city_infos as $city_name => $distance){
            if($distance != self::INFINITE && $distance != 0){
                $list_adjacent_cities[$city_name] = $distance;
            }
        }
        return $list_adjacent_cities;
    }

    /**
     * calcul le chemin le plus court entre 2 points, en utilisant l'algorithme de dijkstra
     * @param $from string : ville de départ
     * @param $to string : ville d'arrivée
     * @return array
     */
    public function findShortedPath($from,$to){
        $this->_start_city = $from;
        $this->_end_city = $to;

        $this->_initMatrix();

        _log("<h3 class='primary'>étape 3 : On construit une matrice permettant de savoir pour chaque ville, la distance parcouru pour y parvenir (cost), et de quelle ville on venait ( previous ): </h3>");
        _log("<blockquote class='secondary'>On flag la ville de départ avec un cost de 0 afin d'être sur de commencer à analyser cette ville en premier dans la suite de l'algo</blockquote>");
        if(DEBUG_MODE){
            _log("<pre>");
            print_r($this->_matrix);
            _log("</pre>");
        }

        _log("<h3 class='primary'>étape 4 : On boucle tant qu'il reste des villes à visiter: </h3>");
        _log('<div class="secondary">');
        $i = 0;
        while(!empty($this->_list_unvisited_cities)){
            _log("<h4 class='primary'>boucle n° ".$i." :</h4>");

            $city_name_with_min_cost = $this->_getCityNameWithMinCost();
            _log("<h4>récupération de la ville avec le cost le plus faible : ".$city_name_with_min_cost."</h4>");

            //on indique qu'on a visité la ville courante
            $index_to_remove = array_search($city_name_with_min_cost, $this->_list_unvisited_cities);
            unset($this->_list_unvisited_cities[$index_to_remove]);
            _log("<h4>on retire cette ville (".$city_name_with_min_cost.") des villes a visiter</h4>");
            _log("<h4>liste des villes restant à visiter : </h4>");
            if(DEBUG_MODE){
                _log("<pre>");
                print_r($this->_list_unvisited_cities);
                _log("</pre>");
            }

            _log("<h4>on récupère la listes des villes adjacentes de la ville (".$city_name_with_min_cost.") : </h4>");
            $list_adjacent_cities = $this->_getAdjacentCities($city_name_with_min_cost);
            if(DEBUG_MODE){
                _log("<pre>");
                print_r($list_adjacent_cities);
                _log("</pre>");
            }
            if(is_array($list_adjacent_cities)){
                _log("<h4>on itère sur chaque ville adjacente, et on regarde dans la matrice le 'cost' associé.</h4>");
                if($i == 0){
                    _log("<h4>Si ce 'cost' est < 0, on est jamais passé par cette ville, on met le cost à jour.</h4>");
                    _log("<h4>Si ce 'cost' est > 0, on est déjà passé par cette ville, en provenant d'un autre ville.</h4>");
                    _log("<h4>Il faut alors analyser si le 'cost' en provenant de la ville actuelle serait plus faible ou non, si c'est le cas, on le met à jour</h4>");
                }
                foreach($list_adjacent_cities as $adjacent_city_name => $distance_from_adjacent_city){
                    _log("<h4>ville adjacente : ".$adjacent_city_name.", cost : ".$this->_matrix[$adjacent_city_name]['cost']."</h4>");
                    if($this->_matrix[$adjacent_city_name]['cost'] < 0
                        OR
                        $this->_matrix[$adjacent_city_name]['cost'] > ($this->_matrix[$city_name_with_min_cost]['cost'] + $distance_from_adjacent_city)){

                        if($this->_matrix[$adjacent_city_name]['cost'] < 0){
                            _log("<h4>le cost est < 0, on est jamais passé par cette ville, on met le cost à jour.</h4>");
                        }else{
                            _log("<h4>le 'cost' en provenant de la ville actuelle (".($this->_matrix[$city_name_with_min_cost]['cost'] + $distance_from_adjacent_city)."), est plus faible, on le met à jour.</h4>");
                        }

                        if($this->_matrix[$city_name_with_min_cost]['cost'] == self::INFINITE){
                            $this->_matrix[$city_name_with_min_cost]['cost'] = 0;
                        }

                        $this->_matrix[$adjacent_city_name]['cost'] = $this->_matrix[$city_name_with_min_cost]['cost'] + $distance_from_adjacent_city;
                        $this->_matrix[$adjacent_city_name]['previous'] = $city_name_with_min_cost;
                        // on ajoute la ville adjacente proche dans les ville non visitées ( afin de forcer son retraitement par la suite )
                        if(!in_array($adjacent_city_name, $this->_list_unvisited_cities)){
                            $this->_list_unvisited_cities[] = $adjacent_city_name;
                        }
                    }else{
                        _log("<h4>le 'cost' en provenant de la ville actuelle (".($this->_matrix[$city_name_with_min_cost]['cost'] + $distance_from_adjacent_city)."), est supérieur que celui provenant de la ville ".$this->_matrix[$adjacent_city_name]['previous']." (".$this->_matrix[$adjacent_city_name]['cost']."), on ne met pas le 'cost' à jour.</h4>");
                    }
                }
            }

            _log("<h4>aperçu des 'cost' de la matrice à l'issue de la boucle n°".$i." : </h4>");
            if(DEBUG_MODE){
                _log("<pre>");
                print_r($this->_matrix);
                _log("</pre>");
            }

            $i++;
        }
        _log("</div>");


        _log("<h4 class='primary'>Dernière étape, on reconstruit le cheminement en partant de la ville d'arrivée (".END_CITY.") et en analysant sa ville précédente (previous), etc, de manière recursive, jusqu'à arriver à la ville de départ (".START_CITY.")</h4>");
        $shortest_path = array();
        $current_city = $this->_matrix[END_CITY];
        $current_city["name"] = END_CITY;
        $total_cost = $current_city['cost'];
        while($current_city['previous'] != null){
            array_unshift($shortest_path, $current_city);
            $previous_city = $current_city['previous'];
            $current_city = $this->_matrix[$previous_city];
            $current_city["name"] = $previous_city;
        }
        $current_city = $this->_matrix[START_CITY];
        $current_city["name"] = START_CITY;
        array_unshift($shortest_path, $current_city);

        _log("<h3 class='important'>Ainsi, l'algorithme de Dijkstra nous permet de savoir que le chemin le plus court pour aller de la ville '".START_CITY."', à la ville '".END_CITY."' est : </h3>");
        $list_city_names_in_path = array();
        foreach($shortest_path as $city){
            $list_city_names_in_path[] = $city['name'];
        }
        _log(implode(" -> ",$list_city_names_in_path));
        _log("<h3 class='important'>avec une distance (cost) total de : ".$total_cost."</h3>");
        if(DEBUG_MODE){
            _log("<pre>");
            print_r($shortest_path);
            _log("</pre>");
        }

        return $shortest_path;
    }

}

?>
<!DOCTYPE html>
<html lang="fr">

<head>
<meta charset="UTF-8">

<style type="text/css">
    * {font-family: monospace;}
    .important {color: red;}
    .primary {color: black;}
    .secondary {color: gray;}
</style>

</head>
<body>
<?php

$graph_draw = <<<EOF
<pre>
+----- Aperçu du graph ----+
+------- non orienté ------+
+--------------------------+
|                          |
|  A--6---B--15--D--30--F  |
|   \     |      |     /   |
|    \    |      |    /    |
|     10  5      5  20     |
|      \  |      |  /      |
|       \ |      | /       |
|         C--10--E         |
|                          |
+--------------------------+
</pre>
EOF;
_log($graph_draw);

_log('<pre>En entrée, un seul tableau : list_section :
$list_sections = array(
    "A;B;6",
    "A;C;10",
    "B;D;15",
    "B;C;5",
    "C;E;10",
    "D;E;5",
    "D;F;30",
    "E;F;20",
);
</pre>');

$list_sections = array(
    "A;B;6",
    "A;C;10",
    "B;D;15",
    "B;C;5",
    "C;E;10",
    "D;E;5",
    "D;F;30",
    "E;F;20",
);

/*préparation des données recues en entrée: création d'un tableau représentant un graphe utilisable par la class Dijkstra*/
$list_distance_between_cities = array();
foreach($list_sections as $a_section){
    list($from,$to,$distance) = explode(';',$a_section);
    $list_distance_between_cities[$from][$from] = 0;
    $list_distance_between_cities[$to][$to] = 0;
    $list_distance_between_cities[$from][$to] = $distance;
    $list_distance_between_cities[$to][$from] = $distance;
}

$dijkstra = new Dijkstra($list_distance_between_cities);
$dijkstra->findShortedPath(START_CITY, END_CITY);

?>
</body>
</html>