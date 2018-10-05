var map, pointLayer, iconSource, view;
var key = 'AIzaSyCITy8V79lX5SJSpw0OC-m-sP-LlRzv37U';

jQuery(document).ready(function() {


    var icons = [];

    var iconStyle = new ol.style.Style({
        image: new ol.style.Circle({
            radius: 3,
            fill: new ol.style.Fill({
                color: [255, 204, 102, 1]
            }),
            stroke: new ol.style.Stroke({
                color: [255, 204, 102, 1],
                width: 1.5
            })
        }),
        zIndex: 1
    });

    iconSource = new ol.source.Vector();

    pointLayer = new ol.layer.Vector({
        source: iconSource,
        style: iconStyle
    });

    function addPointLayer(layer) {
        map.addLayer(layer);
    }
    function removePoint(layer) {
        map.removeLayer(layer);
    }

    view = new ol.View({
        center: ol.proj.fromLonLat([3.0322265624999996, 47.279229002570816]),
        zoom: 5
    })

    map = new ol.Map({
        layers: [
            new ol.layer.Tile({
                source: new ol.source.XYZ({
                    url: 'https://a.tile.openstreetmap.de/tiles/osmde/{z}/{x}/{y}.png'
                }),
                preload: 4
            }),
            pointLayer
        ],
        loadTilesWhileAnimating: true,
        view: view
    });




    map.on("click", function(event) {
        var position = event.coordinate;
        
        position = ol.proj.transform(position, 'EPSG:3857', 'EPSG:4326');
        
        setCoordsInputs(position[1], position[0], latInput, longInput);
        centerMap(position[1], position[0]);
    });


});


function searchCoordinates(callback) {
    
    // On récupère l'adresse entrée et on la recherche sur la carte à l'aide de Google maps API 
    
    var coords;

    var addressName = $(addressNameID).val();
    var CP = $(CPID).val();
    var city = $(cityID).val();
    
    console.log(city);
    var address = addressName + ' ' + CP + ' ' + city;

    // var address = '1600+Amphitheatre+Parkway,+Mountain+View,+CA';
    var address = encodeURI(address);

    $.getJSON(
        "https://maps.googleapis.com/maps/api/geocode/json?address=" + address + "&key=" + key,
        function () {
            console.log("TODO faire un petit loader sur la map");
        })
    .done(function (data) {
        console.log(data);
        if (data.status == "ZERO_RESULTS") {
            
            //TODO : améliorer le rendu quand il n'y a pas d'adresse reconue
            alert('Aucune adresse trouvée ! Merci de revérifiez vos champs: adresse, code postal et ville.');
        } else {
            var location = data.results[0].geometry.location;
            
            callback(location);
            //alert('submit');
            $( "[name=fos_user_registration_form]" ).submit();
        }
    })
    .fail(function () {
        alert('Une erreur est survenue avec l\'appel pour trouver l\'adresse recherchée via l\'application Google API');
    })
    .always(function () {
        console.log("complete");
    });
    
    
};
    
function setCoordinates(coords) {

    //On intègre les coordonnées dans le formulaire quand la personne recherche directement l'adresse
    setCoordsInputs(coords.lat, coords.lng, latInput, longInput);
    
    // Ici on créé l'objet pour le point avant de l'inclure dans la map
    centerMap(coords.lat, coords.lng);
    
}

function centerMap(lat, long) {
    // On clear le point précédent
    iconSource.clear();
    
    // On projète les coordonnées dans la projection de open street map
    
    var coordinates = ol.proj.fromLonLat([long, lat]);
    
    
    // On créé un nouveau item et on l'injecte dans la layer (mise à jour automatiquement)
    var feature = new ol.Feature();
    var geometry = new ol.geom.Point(coordinates);
    feature.setGeometry(geometry);
    iconSource.addFeature(feature);
    
    // On centre la carte sur le nouveau point (petite animation à la carte)
    view.animate({
        center: coordinates,
        duration: 1000
    });
}
    
function setCoordsInputs(lat, long, latInput, longInput) {

    $('#mapIdentifiers').fadeIn(1000);

    $(latInput).val(lat);
    $(longInput).val(long);
}