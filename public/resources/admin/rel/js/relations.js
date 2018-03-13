$(window).on('load', function() {
// set up SVG for D3
var padding = 40,
    width = $(window).width() - padding,
    height = $(window).height() - padding,
    colors = d3.scale.category10();

var svg = d3.select('svg');

// set up initial nodes and links
//  - nodes are known by 'id', not by index in array.
//  - reflexive edges are indicated on the node (as a bold black circle).
//  - links are always source < target; edge directions are set by 'left' and 'right'.

var data = window.relData || JSON.parse(atob("eyJub2RlcyI6W10sImxpbmtzIjpbXX0="));

nodes = data.nodes;
lastNodeId = nodes.length-1
links = setupLinks(data.links);

// init D3 force layout
window.force = d3.layout.force()
    .nodes(nodes)
    .links(links)
    .size([width, height])
    .linkDistance(150)
    .charge(-500)
    .on('tick', tick);

// define arrow markers for graph links
svg.append('svg:defs').append('svg:marker')
    .attr('id', 'end-arrow')
    .attr('viewBox', '0 -5 10 10')
    .attr('refX', 6)
    .attr('markerWidth', 3)
    .attr('markerHeight', 3)
    .attr('orient', 'auto')
    .append('svg:path')
    .attr('d', 'M0,-5L10,0L0,5')
    .attr('fill', '#000');

svg.append('svg:defs').append('svg:marker')
    .attr('id', 'start-arrow')
    .attr('viewBox', '0 -5 10 10')
    .attr('refX', 4)
    .attr('markerWidth', 3)
    .attr('markerHeight', 3)
    .attr('orient', 'auto')
    .append('svg:path')
    .attr('d', 'M10,-5L0,0L10,5')
    .attr('fill', '#000');

var graph = svg.append('svg:g').attr('id', 'graph');
  
graph.call(d3.behavior.zoom().scaleExtent([0.1, 8]).on("zoom", zoom));  
  
// line displayed when dragging new nodes
var drag_line = graph.append('svg:path')
    .attr('class', 'link dragline hidden')
    .attr('d', 'M0,0L0,0');

// handles to link and node element groups
var path = graph.append('svg:g').selectAll('path'),
    circle = graph.append('svg:g').selectAll('g');

// mouse event vars
var selected_node = null,
    selected_link = null,
    mousedown_link = null,
    mousedown_node = null,
    mouseup_node = null;

function resetMouseVars() {
    mousedown_node = null;
    mouseup_node = null;
    mousedown_link = null;
}

// update force layout (called automatically each iteration)
function tick() {
    // draw directed edges with proper padding from node centers
    path.attr('d', function (d) {
        d = d || {source: {},target: {}};
        var deltaX = (d.target.x||0) - (d.source.x||0),
            deltaY = (d.target.y||0) - (d.source.y||0),
            dist = Math.sqrt(deltaX * deltaX + deltaY * deltaY),
            normX = dist ? deltaX / dist : -0.05,
            normY = dist ? deltaY / dist : -0.05,
            sourcePadding = d.left ? 17 : 12,
            targetPadding = d.right ? 17 : 12,
            sourceX = (d.source.x||0) + (sourcePadding * normX),
            sourceY = (d.source.y||0) + (sourcePadding * normY),
            targetX = (d.target.x||0) - (targetPadding * normX),
            targetY = (d.target.y||0) - (targetPadding * normY);
        return 'M' + sourceX + ',' + sourceY + 'L' + targetX + ',' + targetY;
    });

    circle.attr('transform', function (d) {
        return 'translate(' + (d.x||0) + ',' + (d.y||0) + ')';
    });
}

// update graph (called when needed)
function restart() {
    // path (link) group
    path = path.data(links);

    // update existing links
    path.classed('selected', function (d) {
        return d === selected_link;
    })
        .style('marker-start', function (d) {
        return d.left ? 'url(#start-arrow)' : '';
    })
        .style('marker-end', function (d) {
        return d.right ? 'url(#end-arrow)' : '';
    });


    // add new links
    path.enter().append('svg:path')
        .attr('class', 'link')
        .classed('selected', function (d) {
        return d === selected_link;
    })
        .style('marker-start', function (d) {
        return d.left ? 'url(#start-arrow)' : '';
    })
        .style('marker-end', function (d) {
        return d.right ? 'url(#end-arrow)' : '';
    })
        .on('mousedown', function (d) {
        if (d3.event.ctrlKey) return;

        // select link
        mousedown_link = d;
        if (mousedown_link === selected_link) selected_link = null;
        else selected_link = mousedown_link;
        selected_node = null;
        
        if(selected_link) openEditor();
        //else $("section.node").hide();
            
        restart();
    });

    // remove old links
    path.exit().remove();


    // circle (node) group
    // NB: the function arg is crucial here! nodes are known by id, not by index!
    circle = circle.data(nodes, function (d) {
        return d.id;
    });

    // update existing nodes (reflexive & selected visual states)
    circle.selectAll('circle')
        .style('fill', function (d) {
        return (d === selected_node) ? 
            d3.rgb(colors(d.group || d.id)).brighter().toString() : colors(d.group || d.id);
    })
        .classed('reflexive', function (d) {
        return d.reflexive;
    });

    // add new nodes
    var g = circle.enter().append('svg:g');

    g.append('svg:circle')
        .attr('class', 'node')
        .attr('r', 12)
        .style('fill', function (d) {
        return (d === selected_node) ? 
            d3.rgb(colors(d.group || d.id)).brighter().toString() : colors(d.group || d.id);
    })
        .style('stroke', function (d) {
        return d3.rgb(colors(d.group || d.id)).darker().toString();
    })
        .classed('reflexive', function (d) {
        return d.reflexive;
    })
        .on('mouseover', function (d) {
        if (!mousedown_node || d === mousedown_node) return;
        // enlarge target node
        d3.select(this).attr('transform', 'scale(1.1)');
    })
        .on('mouseout', function (d) {
        if (!mousedown_node || d === mousedown_node) return;
        // unenlarge target node
        d3.select(this).attr('transform', '');
    })
        .on('mousedown', function (d) {
        if (d3.event.ctrlKey) return;

        // select node
        mousedown_node = d;
        if (mousedown_node === selected_node) selected_node = null;
        else selected_node = mousedown_node;
        selected_link = null;
            
        if(selected_node) openEditor();
        //else $("section.node").hide();

        // reposition drag line
        drag_line.style('marker-end', 'url(#end-arrow)')
            .classed('hidden', false)
            .attr('d', 
                  'M' + mousedown_node.x + 
                  ',' + mousedown_node.y + 
                  'L' + mousedown_node.x + 
                  ',' + mousedown_node.y
                 );

        restart();
    })
        .on('mouseup', function (d) {
        if (!mousedown_node) return;

        // needed by FF
        drag_line.classed('hidden', true)
            .style('marker-end', '');

        // check for drag-to-self
        mouseup_node = d;
        if (mouseup_node === mousedown_node) {
            resetMouseVars();
            return;
        }

        // unenlarge target node
        d3.select(this).attr('transform', '');

        // add link to graph (update if exists)
        // NB: links are strictly source < target; 
        // arrows separately specified by booleans
      
      /*
        var source, target, direction;
        if (mousedown_node.id < mouseup_node.id) {
            source = mousedown_node;
            target = mouseup_node;
            direction = 'right';
        } else {
            source = mouseup_node;
            target = mousedown_node;
            direction = 'left';
        }

        var link;
        link = links.filter(function (l) {
            return (l.source === source && l.target === target);
        })[0];

        if (link) {
            link[direction] = true;
        } else {
            link = {
                source: source,
                target: target,
                left: false,
                right: false
            };
            link[direction] = true;
            links.push(link);
        }

        // select new link
        selected_link = link;
        selected_node = null;
        restart();
        
        */
    });
  
    // show node IDs
    g.append('svg:text')
        .attr('x', 0)
        .attr('y', 4)
        .attr('class', 'id')
        .text(function (d) {
        return d.name || d.id;
    });

    g.attr('id', function (d) { return "node_" + d.id });
  
    // remove old nodes
    circle.exit().remove();

    // set the graph in motion
    force.start();
}
  
  window.redraw = restart;

function mousedown() {
    // prevent I-bar on drag
    //d3.event.preventDefault();

    // because :active only works in WebKit?
    svg.classed('active', true);

    /*if (
        d3.event.ctrlKey || 
        mousedown_node || 
        mousedown_link
    )*/ return;

    // insert new node at point
    var point = d3.mouse(this),
        node = {
            id: ++lastNodeId,
            reflexive: false
        };
    node.x = point[0];
    node.y = point[1];
    nodes.push(node);

    restart();
}

function mousemove() {
    if (!mousedown_node) return;

    // update drag line
    drag_line.attr('d', 
                   'M' + mousedown_node.x + 
                   ',' + mousedown_node.y + 
                   'L' + d3.mouse(this)[0] + 
                   ',' + d3.mouse(this)[1]
                  );

    restart();
}

function mouseup() {
    if (mousedown_node) {
        // hide drag line
        drag_line.classed('hidden', true)
            .style('marker-end', '');
    }

    // because :active only works in WebKit?
    svg.classed('active', false);

    // clear mouse event vars
    resetMouseVars();
}

function spliceLinksForNode(node) {
    var toSplice = links.filter(function (l) {
        return (l.source === node || l.target === node);
    });
    toSplice.map(function (l) {
        links.splice(links.indexOf(l), 1);
    });
}

// only respond once per keydown
var lastKeyDown = -1;

function keydown() {
    if($("input:focus, textarea:focus").length)
        return;
    d3.event.preventDefault();

    if (lastKeyDown !== -1) return;
    lastKeyDown = d3.event.keyCode;

    // ctrl
//    if (d3.event.keyCode === 16) {
        circle.call(force.drag);
        svg.classed('ctrl', true);
//    }

    if (!selected_node && !selected_link) return;
    switch (d3.event.keyCode) {
        case 8:
            // backspace
        case 46:
            // delete
            if (selected_node) {
                nodes.splice(nodes.indexOf(selected_node), 1);
                spliceLinksForNode(selected_node);
            } else if (selected_link) {
                links.splice(links.indexOf(selected_link), 1);
            }
            selected_link = null;
            selected_node = null;
            restart();
            break;
        case 66:
            // B
            if (selected_link) {
                // set link direction to both left and right
                selected_link.left = true;
                selected_link.right = true;
            }
            restart();
            break;
        case 76:
            // L
            if (selected_link) {
                // set link direction to left only
                selected_link.left = true;
                selected_link.right = false;
            }
            restart();
            break;
        case 82:
            // R
            if (selected_node) {
                // toggle node reflexivity
                selected_node.reflexive = !selected_node.reflexive;
            } else if (selected_link) {
                // set link direction to right only
                selected_link.left = false;
                selected_link.right = true;
            }
            restart();
            break;
        case 90:
            //Zoom
            
            break;
    }
}

function keyup() {
    lastKeyDown = -1;

    // ctrl
    if (d3.event.keyCode === 16) {
        circle.on('mousedown.drag', null)
            .on('touchstart.drag', null);
        svg.classed('ctrl', false);
    }
}
  
function zoom() {
  if(!mousedown_node)
  graph.attr("transform", "translate(" + d3.event.translate + ")scale(" + d3.event.scale + ")");
}

// app starts here
svg.on('mousedown', mousedown)
  .on('mousemove', mousemove)
  .on('mouseup', mouseup);
d3.select(window)
  .on('keydown', keydown)
  .on('keyup', keyup);
restart();

function openEditor(){
    var elm = selected_node || selected_link || {};
    var edge = !!elm.source;
    $("section.node > h1").text((edge ? "Relationship" : "Character") + " Settings");
    
    $("#name").val(elm.name||"");
//    $("#archetype").selectpicker('deselectAll').selectpicker('val', elm.archetype);
    $("#desc").val(elm.desc||"");
    $("#notes").val(elm.notes||"");
    $("#fate").val(elm.fate||"");  
//    $("#group").selectpicker('deselectAll').selectpicker('val', elm.group);
//    $("#type").selectpicker('deselectAll').selectpicker('val', elm.type);
    $("section.node").slideDown(300);
  
    if(edge) {
        $(".node .char").hide();
        $(".node .rel").show();
      
        $(".node .rel .left, .node .rel .right")
          .removeClass("fa-arrow-circle-o-right fa-arrow-circle-o-left")
          .addClass("fa-circle-o");
        
        if(elm.left) $(".node .rel .left").addClass("fa-arrow-circle-o-left");
        if(elm.right) $(".node .rel .right").addClass("fa-arrow-circle-o-right");
        
        $(".node .rel .source")
          .text(elm.source.name || "Node " + elm.source.id)
          .css({backgroundColor:colors(elm.source.group || elm.source.id)});
        $(".node .rel .target")
          .text(elm.target.name || "Node " + elm.target.id)
          .css({backgroundColor:colors(elm.target.group || elm.target.id)});
    } else {
        $(".node .char").show();
        $(".node .rel").hide();
    }
}

$("section.node [type=submit]").on("click", function(e){
    e.preventDefault();
    var elm = selected_node || selected_link || {};
    var vals = ["name", "desc", "notes", "fate", "archetype", "group", "type"];
    vals.forEach(function (val) {
      if ($("#" + val).length && $("#" + val).val().length) elm[val] = $("#" + val).val();
      else delete elm[val];  
    });

    if (elm.type) { 
      $(".link.selected").css('stroke', elm.type ? colors(elm.type) : '#000');
    }

    $("section.node").slideUp(30).slideDown(100);
    $("#node_" + elm.id + " > text").text(elm.name);
    $("#node_" + elm.id + " > .node").css({
      'stroke': d3.rgb(colors(elm.group || elm.id)).darker().toString(),
      'fill': d3.rgb(colors(elm.group || elm.id)).toString()
    });
    
});

$("section.node .rel button").on("click", function(e){
//    e.preventDefault();
/*
    var t = $(this).hasClass("source") ? 
        selected_node.source : selected_node.target;
*/
});

function cleanNode(n) {
    var node = {};
    node.id = n.id;
    node.reflexive = n.reflexive;
    node.name = n.name || "";
    if (n.archetype) node.archetype = n.archetype;
    node.desc = n.desc || "";
    node.notes = n.notes || "";
    node.fate = n.fate || "";  
    if (n.group) node.group = n.group;
    return node;
}

function cleanLink(l) {
    var link = {};
    link.source = cleanNode(l.source);
    link.target = cleanNode(l.target);
    link.left = l.left;
    link.right = l.right;
    link.name = l.name || "";
    link.desc = l.desc || "";
    if (l.type) link.type = l.type;  
    return link;
}

function nodeById(id) {
    return nodes.filter(function(n){ 
        return n.id === id ? n : false; 
    })[0];
}

function setupLinks(l){
    return l.map(function(link){
        link.source = nodeById(link.source.id);
        link.target = nodeById(link.target.id);
        return link;
    });
}
  
function addOption (sel, val) {
  $(sel).append($('<option/>', {
    'value': val,
    'text': val
  }).prepend(
    $('<i class="fa fa-square" aria-hidden="true"></i>')
      .css({
        "margin-right": ".5em",
        "color": d3.rgb(colors(val)).brighter().toString() 
      })
  ));
  $(sel).selectpicker('refresh').selectpicker('val', val);
}

$(".info").on("click", function(e){
  $(".usage").toggle();
});
  
$("#add-group").on('click', function() {
  addOption('#group', $("#new-group-name").val() );
  $("#new-group-name").val(null);
});

$("#add-archetype").on('click', function() {
  addOption('#archetype', $("#new-archetype-name").val() );
  $("#new-archetype-name").val(null);
});  
  
$("#add-type").on('click', function() {
  addOption('#type', $("#new-type-name").val() );
  $("#new-type-name").val(null);
});
  
$("section.output h1").on("click", function(e){
    e.preventDefault();
    var data = {nodes:[], links:[]}, node, link;
    
    nodes.forEach(function(n,i){ data.nodes.push(cleanNode(n)); });
    links.forEach(function(l,i){ data.links.push(cleanLink(l)); });
        
    $("section.output textarea").val(/*btoa(*/JSON.stringify(data)/*)*/);
    $("section.output form").slideToggle(300);    
});

$("section.output .load").on("click", function(e){
    e.preventDefault();
    var data = JSON.parse(/*atob(*/$("section.output textarea").val()/*)*/);
  
    data.nodes.forEach(function (node) {
      node.x = 0;
      node.y = 0;
      if(node.group && !$("#group option:contains(" + node.group + ")").length) {
         addOption('#group', node.group);
      }
      if(node.archetype && !$("#group option:contains(" + node.archetype + ")").length) {
         addOption('#archetype', node.archetype);
      }      
      nodes.push(node);
      restart();
    });
  
    data.links.forEach(function (link) {
      if(link.type && !$("#type option:contains(" + link.type + ")").length) {
         addOption('#type', link.type);
      } 
    });
  
    links = setupLinks(data.links);  
    lastNodeId = nodes.length; 
    restart();
  
    $("section.output h1").trigger("click");
});

$("section.node, section.node .rel, section.output form").hide();
});