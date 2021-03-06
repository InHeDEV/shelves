<?php
require_once('db.php');
require_once('./../wp-load.php');


$s_id = $_GET['shelve_id'];

$result = $conn->prepare("SELECT * FROM myshop_shelves WHERE tbl_image_id = '{$s_id}'");
$result->execute();
$row = $result->fetch();
$img_src = $row['image_location'];

?>

<!DOCTYPE html>
<html>
<head>
    <title>Select Product</title>
    <meta charset="utf-8">
    <!--<meta http-equiv="imagetoolbar" content="no"/>-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <style type="text/css">
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
        }

        body {
            width: 100%;
            height: 100%;
            background: #ffffff;
        }

        #map {
            position: absolute;
            height: 100%;
            width: 100%;
            background-color: #FFFFFF;
        }

        #slider {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
    <link href="css/bootstrap.css" rel="stylesheet" type="text/css" media="screen">

    <script src="//cdn.polyfill.io/v2/polyfill.min.js?features=requestAnimationFrame,Element.prototype.classList,URL,fetch,Function.prototype.bind,es5&flags=always,gated&unknown=polyfill"
            type="text/javascript"></script>
    <script src="//cdn.jsdelivr.net/gh/openlayers/openlayers.github.io@master/en/v6.3.1/build/ol.js"></script>
    <link rel="stylesheet" href="//cdn.jsdelivr.net/gh/openlayers/openlayers.github.io@master/en/v6.3.1/css/ol.css">

    <script src="js/jquery.js" type="text/javascript"></script>
    <script src="js/bootstrap.js" type="text/javascript"></script>
    <style>
        @media (max-width: 768px) {
            #slider {
                width: 73px;
            }
        }
    </style>

</head>
<body>

<div id="map">

</div>

<div style="position: absolute;top: 10px;left: 50px;">
    <button onclick="location.href='<?php echo get_site_url(); ?>'">Dashboard</button>
    <button onclick="location.href='<?php echo get_site_url() . '/cart'; ?>'">View Cart</button>
</div>
<input id="slider" type="range" min="0" max="1" step="0.1" value="1" oninput="layer.setOpacity(this.value)"
       placeholder="">

<script type="text/javascript">

    var rCnt = 0.1;
    var cCnt = 0.1;

    var mapExtent = [0.00000000, -8213.00000000, 11355.00000000, 0.00000000];

    var projection = new ol.proj.Projection({
        code: 'xkcd-image',
        units: 'pixels',
        extent: mapExtent
    });

    var mapMinZoom = 0;
    var mapMaxZoom = 6;
    var mapMaxResolution = 1.00000000;

    var mapResolutions = [];
    for (var z = 0; z <= mapMaxZoom; z++) {
        mapResolutions.push(Math.pow(2, mapMaxZoom - z) * mapMaxResolution);
    }

    var mapTileGrid = new ol.tilegrid.TileGrid({
        extent: mapExtent,
        minZoom: mapMinZoom,
        resolutions: mapResolutions,
        tileSize: [256, 256]

    });

    var layer = new ol.layer.Tile({
        source: new ol.source.XYZ({
            projection: 'PIXELS',
            tileGrid: mapTileGrid,
            tilePixelRatio: 1.00000000,
            url: '<?php echo site_url();?>/wp-content/plugins/myshop-shelf/admin/shelves/uploads/shop1/{z}/{x}/{y}.png',
        })
    });

    var source = new ol.source.Vector({wrapX: false});
    var vector = new ol.layer.Vector({
        source: source
    });

    var map = new ol.Map({
        layers: [layer, vector],
        target: 'map',
        view: new ol.View({
            extent: mapExtent,
        })
    });

    map.getView().fit(mapExtent, map.getSize());

    var pressTimer = 0;
    var circle = new ol.geom.Circle([0, 0], 0, 'XY');
    source.addFeature(new ol.Feature({geometry: circle}));

    var hidden_data;

    var downloadCrop = function (p0, p1) {
        var mapCanvas = document.getElementsByTagName('canvas')[0];

        var l = p0[0] < p1[0] ? p0[0] : p1[0];
        var t = p0[1] < p1[1] ? p0[1] : p1[1];
        var r = p0[0] > p1[0] ? p0[0] : p1[0];
        var b = p0[1] > p1[1] ? p0[1] : p1[1];
        var width = r - l;
        var height = b - t;
        var hidden_canv = document.createElement('canvas');
        hidden_canv.width = width;
        hidden_canv.height = height;

        //Draw the data you want to download to the hidden canvas
        var hidden_ctx = hidden_canv.getContext('2d');

        var img = new Image();
        //hidden_ctx.scale(0.8, 0.8);
        hidden_ctx.drawImage(
            mapCanvas,
            l,//Start Clipping
            t,//Start Clipping
            width,//Clipping Width
            height,//Clipping Height
            0,//Place X
            0,//Place Y
            hidden_canv.width,//Place Width
            hidden_canv.height//Place Height
        );

        hidden_ctx.globalCompositeOperation = 'destination-in';
        hidden_ctx.beginPath();
        hidden_ctx.arc(width / 2, height / 2, width / 2, 0, Math.PI * 2);
        hidden_ctx.closePath();
        hidden_ctx.fill();

        //Create a download URL for the data
        hidden_data = hidden_canv.toDataURL("image/png", 1).replace("image/png", "image/octet-stream");

        $.ajax({
            type: 'POST',
            url: 'addCart.php',
            data: {
                imgBase64: hidden_data,
                sid: "<?php echo $img_src;?>",
            },
            dataType: 'json',
            success: function (data) {
                var res = data.result;
                alert("Successfully Added");
                $.get('./?post_type=product&add-to-cart=' + res, function () {
                    // call back
                });
            }
        });
        return;
        //Make a download link
        var downloadAnchor = document.createElement('a');
        downloadAnchor.setAttribute('download', 'Crop.png');
        downloadAnchor.setAttribute('href', hidden_data);
        downloadAnchor.setAttribute('id', 'download-image');
        document.body.appendChild(downloadAnchor);
        downloadAnchor.click();
        document.body.removeChild(downloadAnchor);
        alert("Successfully saved!");
    };

    var prevEvent;
    var flag = false;

    map.addInteraction(new ol.interaction.Interaction({
        handleEvent: function (e) {
            if (e.type === 'pointerdown' || e.type === 'mousedown') {
                prevEvent = e.pixel;
                flag = true;
            }
            else if (e.type === 'pointerup' || e.type === 'mouseup') {
                if (pressTimer !== 0) {

                    flag = false;
                    clearInterval(pressTimer);
                    pressTimer = 0;

                    var p0 = map.getPixelFromCoordinate([circle.getCenter()[0] - circle.getRadius(), circle.getCenter()[1] - circle.getRadius()]);
                    var p1 = map.getPixelFromCoordinate([circle.getCenter()[0] + circle.getRadius(), circle.getCenter()[1] + circle.getRadius()]);
                    if(circle.getRadius() > 40) {
                        var doDownload = confirm("Do you want to add this to Cart?");
                        circle.setRadius(0);
                        vector.setVisible(false);
                        map.renderSync();
                        if (doDownload)
                            downloadCrop(p0, p1);
                    }
                    circle.setRadius(0);
                    vector.setVisible(false);

                }
            }
            else {
                if (pressTimer !== 0 ) {
                    if((prevEvent[0] - e.pixel[0])*(prevEvent[0] - e.pixel[0]) > 4000 || (prevEvent[1] - e.pixel[1])*(prevEvent[1] - e.pixel[1]) > 4000){
                        clearInterval(pressTimer);
                        pressTimer = 0;
                        circle.setRadius(0);
                        vector.setVisible(false);
                        flag = false;
                    }
                }
            }
            if (flag === true) {
                if (pressTimer !== 0) {
                    clearInterval(pressTimer);
                    pressTimer = 0;
                }
                circle.setCenter(map.getCoordinateFromPixel(e.pixel));
                vector.setVisible(true);
                pressTimer = setInterval(function () {
                    if (circle.getRadius() < map.getView().getResolution() * 200)
                        circle.setRadius(circle.getRadius() + map.getView().getResolution() * 2);
                }, 10);
            }
            return 1;
        }
    }));

</script>
