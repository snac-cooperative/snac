var labelType, useGradients, nativeTextSupport, animate;

// this is a modified version of an example file that came with Nicolas Garcia
// Belmonte's JavaScript InfoVis Toolkit http://thejit.org/downloads/Jit-2.0.0b.zip
// http://thejit.org/static/v20/Jit/Examples/RGraph/example1.js
// http://thejit.org/static/v20/Jit/Examples/RGraph/example1.html

// JSON is created with this rexster-extesion written in gremlin
// http://code.google.com/p/eac-graph-load/source/browse/rexster-extension/src/main/groovy/org/cdlib/snac/kibbles/TheJit.groovy

(function() {
  var ua = navigator.userAgent,
      iStuff = ua.match(/iPhone/i) || ua.match(/iPad/i),
      typeOfCanvas = typeof HTMLCanvasElement,
      nativeCanvasSupport = (typeOfCanvas == 'object' || typeOfCanvas == 'function'),
      textSupport = nativeCanvasSupport
        && (typeof document.createElement('canvas').getContext('2d').fillText == 'function');
  //I'm setting this based on the fact that ExCanvas provides text support for IE
  //and that as of today iPhone/iPad current text support is lame
  labelType = (!nativeCanvasSupport || (textSupport && !iStuff))? 'Native' : 'HTML';
  nativeTextSupport = labelType == 'Native';
  useGradients = nativeCanvasSupport;
  animate = !(iStuff || !nativeCanvasSupport);
})();

function log(a) {console.log&&console.log(a);}

function init(){


    //init RGraph
    var rgraph = new $jit.RGraph({
        //Where to append the visualization
        injectInto: 'infovis',
        //Optional: create a background canvas that plots
        //concentric circles.
        background: {
          CanvasStyles: {
            strokeStyle: '#555'
          }
        },
        //Add navigation capabilities:
        //zooming by scrolling and panning.
        Navigation: {
          enable: true,
          panning: true,
          zooming: 20
        },
        //Set Node and Edge styles.
        Node: {
            color: '#ddeeff', type: 'none'
        },

        Edge: {
          color: '#C17878',
          lineWidth:0.25
        },


        onBeforeCompute: function(node){
            var t = new Date();
            console.log(node);
            // snac: load in the graph for the new center node
            $.ajax({
                url: snacUrl+"/visualize/connection_data/"+node.data.dbid+"?degree=1",
                success: function(json){
                    var nodes = json.nodes;
                    var i = 0;
                    nodes.forEach(function(node, k) {
                        if (node.dgr == "x0") {
                            i = k;
                        }
                    });

                    if (i > 0) {
                        var tmp = nodes[i];
                        nodes[i] = nodes[0];
                        nodes[0] = tmp;
                    }

                    nodes.forEach(function(node, k) {
                        node.name = node.caption;
                        delete node.caption;
                        delete node.dgr;
                        delete node.root;
                        node.data = new Array();
                        node.data.dbid = node.dbid;
                        node.bylabel = new Array();
                        node.filename = node.name;
                        node.id = node.id.toString();
                        node.adjacencies = new Array();
                        json.edges.forEach(function(edge) {
                            if (edge.source.toString() == node.id) {
                                console.log(edge);
                                node.adjacencies.push(edge.target.toString());
                            }
                        });
                    });
                    // snac: add the new json graph to the displayed graph
                    rgraph.op.sum(nodes, {type: 'nothing', id: node.id });
                    rgraph.graph.computeLevels(node.id);
                    // snac: this trims nodes that are far away from where we
                    // are now centered
                    node.eachLevel(5,6, function(deep) {
                        // snac: this setTimeout should give control back to
                        // the browser after each node delete the idea is
                        // to try to prevent UI lockups and "unresponsive
                        // script" dialogs
                        setTimeout(function() {
                            rgraph.graph.removeNode(deep.id);
                            rgraph.labels.clearLabels();
                        }, 0);
                    });
                    rgraph.refresh(true);
                    rgraph.compute('end');

                }
            });
        },

        //Add the name of the node in the correponding label
        //and a click handler to move the graph.
        //This method is called once, on label creation.
        onCreateLabel: function(domElement, node){
            domElement.innerHTML = node.name;
            //console.log(node);
            domElement.onclick = function(){
                rgraph.onClick(node.id, {
                    onComplete: function() {
                    }
                });
            };
            // snac: change the label appearance on mouse over
            domElement.onmouseover = function(){
                domElement.style.color="#000";
                domElement.style.border="1px solid";
                domElement.style.zIndex=5;
                domElement.style.backgroundColor="#fff";
                domElement.style.paddingLeft="0.1em";
                //add some function to highlight all the edges that touch this node
                //log(node.id);
            };
            // snac: change the appearance back on mouse out
            domElement.onmouseout = function(){
                domElement.style.color="#ccc";
                domElement.style.border="0";
                domElement.style.zIndex=0;
                domElement.style.backgroundColor="transparent";
            };
        },
        //Change some label dom properties.
        //This method is called each time a label is plotted.
        onPlaceLabel: function(domElement, node){

            var style = domElement.style;
            style.display = '';
            style.cursor = 'pointer';

            if (node._depth <= 1) {
                style.fontSize = "0.8em";
                style.color = "#ccc";

            } else if(node._depth == 2){
                style.fontSize = "0.8em";
                style.color = "#ccc";

            } else {
                style.fontSize = "0.7em";
                style.color = "#ccc";
            }

            var left = parseInt(style.left);
            var w = domElement.offsetWidth;
            style.left = (left - w / 2) + 'px';
        }
    });

    // snac; load in the graph for the original center node
    $.ajax({
        url: snacUrl+"/visualize/connection_data/"+nodeId+"?degree=1",
        success: function(json){
            console.log(json);
            //rgraph.loadJSON(json);
            nodes = json.nodes;
            var i = 0;
            nodes.forEach(function(node, k) {
                if (node.dgr == "x0") {
                    i = k;
                }
            });

            if (i > 0) {
                var tmp = nodes[i];
                nodes[i] = nodes[0];
                nodes[0] = tmp;
            }

            nodes.forEach(function(node, k) {
                node.name = node.caption;
                delete node.caption;
                delete node.dgr;
                delete node.root;
                node.data = new Array();
                node.data.dbid = node.dbid;
                node.bylabel = new Array();
                node.filename = node.name;
                node.id = node.id.toString();

                node.adjacencies = new Array();
                console.log(node);

                json.edges.forEach(function(edge) {
                    if (edge.source.toString() == node.id) {
                        console.log(edge);
                        node.adjacencies.push(edge.target.toString());
                    }
                });
            });
            console.log(nodes);

            //load JSON data
            rgraph.loadJSON(nodes, 0);
            //trigger small animation
            rgraph.graph.eachNode(function(n) {
              var pos = n.getPos();
              pos.setc(-200, -200);
            });
            rgraph.compute('end');
            rgraph.fx.animate({
              modes:['polar'],
              duration: 2000
            });
        }
    });

    // SNAC panZoomControl
    var scaleFactor = 1.1;
    var panSize = 25;
    initialZoom = 1.331;
    rgraph.canvas.scale(initialZoom,initialZoom);

    $('#panUp').click(function(){
        rgraph.canvas.translate(0, panSize * 1/rgraph.canvas.scaleOffsetY);
    });

    $('#panLeft').click(function(){
        rgraph.canvas.translate(panSize * 1/rgraph.canvas.scaleOffsetX, 0);
    });

    $('#panRight').click(function(){
        rgraph.canvas.translate(-panSize * 1/rgraph.canvas.scaleOffsetX, 0);
    });

    $('#panDown').click(function(){
        rgraph.canvas.translate(0, -panSize * 1/rgraph.canvas.scaleOffsetY);
    });

    $('#zoomIn').click(function(){
        rgraph.canvas.scale(scaleFactor,scaleFactor);
    });

    $('#zoomReset').click(function(){
        rgraph.canvas.scale(initialZoom/rgraph.canvas.scaleOffsetX,initialZoom/rgraph.canvas.scaleOffsetY);
    });

    $('#zoomOut').click(function(){
        rgraph.canvas.scale(1/scaleFactor,1/scaleFactor);
    });


}
