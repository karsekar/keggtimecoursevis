<!DOCTYPE html>
<meta charset="utf-8">
<style>

path { 
    stroke: steelblue;
    stroke-width: 2;
    fill: none;
}

#rightcont {
    float: none;
}

#formcont {
    float: none;
}

#leftcont {
    float: left;
}

.bigcont {
    width: 800;
}

.links line {
  stroke: #999;
  stroke-opacity: 0.6;
}

.nodes circle {
  stroke: #fff;
  stroke-width: 1.5px;
}

.nodes circle:hover {
  stroke: #f00;
}

.yaxis path,
.xaxis path {
    fill: none;
    stroke: grey;
    stroke-width: 1;
    shape-rendering: crispEdges;
}

</style>
<body>

<svg id="networkpane" width="1200" height="660"></svg>
<br />
<div class="bigcont">
	<div id="leftcont">
		<p> <?php echo file_get_contents("controls.svg"); ?> </p>
		<p> Current time: <span id="timeind"> </span> seconds </p>
	</div>
	<div id="rightcont">
		<p id="dank">  </p>
		<span><svg id="plot" width="200" height="100"></svg> </span>
	</div>
	<div id="formcont">
		<form id="conditions">
			<input type="radio" name="condition" value="ph5nodex" checked="checked"> pH 5 no dex <br />
			<input type="radio" name="condition" value="ph5nodex_inh"> pH 5 no dex with Antimycin <br />
			<input type="radio" name="condition" value="ph7_4nodex">  pH 7.4 no dex <br />
			<input type="radio" name="condition" value="ph7_4wdex">   pH 7.4 with dex <br />
		</form>
	</div>

</div>

<script src="https://d3js.org/d3.v4.min.js"></script>
<script>

var svg = d3.select("#networkpane"),
    width = +svg.attr("width"),
    height = +svg.attr("height");

var color = d3.scaleOrdinal(d3.schemeCategory20);

var colors = ['#FFB3CC', '#B3B3E6', '#99CCFF', '#80CCB3', '#FFCC66', '#FF9900', '#FF8080', '#9EE284', '#80CCCC', '#8080F7', '#CC99FF', '#DA8E82'];

d3.json("networkGenerator/sce01100Network.json", function(error, graph) {
  if (error) throw error;

  var link = svg.append("g")
      .attr("class", "links")
    .selectAll("line")
    .data(graph.links)
    .enter().append("line")
      .attr("stroke-width", function(d) { return Math.sqrt(d.value); })
      .attr("x1", function(d) { return 0.3*d.x1; })
      .attr("y1", function(d) { return 0.3*(2200 - d.y1); })
      .attr("x2", function(d) { return 0.3*d.x2; })
      .attr("y2", function(d) { return 0.3*(2200 - d.y2); });

  var node = svg.append("g")
      .attr("class", "nodes")
    .selectAll("circle")
    .data(graph.nodes)
    .enter().append("circle")
      .attr("id", function(d) { return d.label; })
      .attr("r", 5)
      .attr("cx", function(d) {return 0.3*d.x})
      .attr("cy", function(d) {return 0.3*(2200-d.y)})
      .attr("class", function(d) {
		if(d.label in annDict) {
			return "nodes annotated";
		} else {
			return "node unannotated";
		}
		})
	.on("click", selectNode)
	.on('mouseover', function(d){
	    d3.select("#".concat(d.label)).style("opacity",'0.5').style("stroke","red")
	})
	.on('mouseout', function(d){
	    d3.select("#".concat(d.label)).style("opacity",'1.0');
	    resetcolors();
	});

  resetcolors();

  node.append("title")
      .text(function(d) { return d.id; });


});

var request = new XMLHttpRequest();
request.open("GET", "kegg2names.json", false);
request.send(null)
var keggDict = JSON.parse(request.responseText);

request.open("GET", "annotation.json", false);
request.send(null)
var annDict = JSON.parse(request.responseText);

//request.open("GET", "yeaststarvation/ph7_4wdex.json", false);
//request.open("GET", "yeaststarvation/ph7_4nodex.json", false);
//request.open("GET", "yeaststarvation/ph5nodex_inh.json", false);
request.open("GET", "yeaststarvation/ph5nodex.json", false);
request.send(null)
var timeDict = JSON.parse(request.responseText);

timeindex = -1;

d3.selectAll("input[name='condition']").on("change", function(){
    filename = "yeaststarvation/".concat(this.value).concat(".json")
    console.log(filename)
    
    request.open("GET", filename, false);
    request.send(null);
    timeDict = JSON.parse(request.responseText);
    updatePlot();
});

d3.selectAll("#conbut").on("click", function() {
  updateTime();
});

d3.selectAll("#backbut").on("click", function() {
  if(timeindex > 0) {
	  timeindex = timeindex-2;
  } else {
	timeindex = timeDict['Time'].length - 2;
  }
  updateTime();
});

d3.select("#playbut").on("click", function() {
		t=setInterval(updateTime,500);	
});


d3.select("#pausebut").on("click", function() {
	clearInterval(t);	
});


d3.select("#stopbut").on("click", function() {
	clearInterval(t);	
	timeindex = timeDict['Time'].length;
	updateTime();
});

function decimalToHexString(number)
{
    if (number < 0)
    {
        number = 0xFFFFFFFF + number + 1;
    }
    temp = number.toString(16).toUpperCase();
    if(temp.length == 1)
    {
	temp = "0".concat(temp);
    }
    return temp;
}

function selectNode(d) {
	idtxt =  d.label.concat(': ');;
	titletxt = idtxt.concat(keggDict[d.label]["names"][0]);
	d3.select("#dank").text(titletxt);
	d3.selectAll(".selected").classed("selected",false);
	d3.select("#".concat(d.label)).classed("selected",true);
	resetcolors();
	updatePlot();
}

function resetcolors() {
	d3.selectAll(".annotated").style("stroke-width",1.5).attr("fill",function(d) { 
			return colors[d.group - 1];
		}).style("stroke", "#fff");

	d3.selectAll(".unannotated").attr("fill","#fff").style("stroke","#ccc").style("stroke-width",1.5);

	d3.selectAll(".selected").style("stroke","#ff0").style("stroke-width",3);

}

function updateTime() {
	timeindex = timeindex + 1;
	dur = timeDict['Time'][timeindex] - timeDict['Time'][timeindex - 1] 
	if(timeindex+1 > timeDict['Time'].length) {
		timeindex = 0;
		dur = 1;
	}
	d3.select("#timeind").transition().text(timeDict['Time'][timeindex]).duration(dur*500);
	d3.selectAll(".annotated").transition().attr("fill", function(d) {
//		code = decimalToHexString(Math.round(255*timeDict['Time'][timeindex]/60));	
		tempVal = timeDict[annDict[d.label]][timeindex];
//		return "#".concat(code).concat("0000");

		if(tempVal > 0) {
			code = decimalToHexString(Math.round(255*tempVal));	
			return "#00".concat(code).concat("00");
		} else {
			code = decimalToHexString(Math.round(-255*tempVal));	
			return "#".concat(code).concat("0000");
		}
	});
}

updateTime();

//code for the plot
var margin = {top: 20, right: 20, bottom: 30, left: 50},
    width = 400 - margin.left - margin.right,
    height = 200 - margin.top - margin.bottom;


// set the ranges
var x = d3.scaleLinear().range([0, width]);
var y = d3.scaleLinear().range([height, 0]);

// define the line
var valueline = d3.line()
    .x(function(d) { return x(d.date); })
    .y(function(d) { return y(d.close); });


// append the svg obgect to the body of the page
// appends a 'group' element to 'svg'
// moves the 'group' element to the top left margin
var plotsvg = d3.select("#plot")
    .attr("width", width + margin.left + margin.right)
    .attr("height", height + margin.top + margin.bottom)
  .append("g")
    .attr("transform",
          "translate(" + margin.left + "," + margin.top + ")");

// Get the data
  // Scale the range of the data

  // Add the valueline path.
  plotsvg.append("path")
      .attr("class", "line")

  // Add the X Axis
  plotsvg.append("g").attr("class","xaxis")
      .attr("transform", "translate(0," + height + ")")

  // Add the Y Axis
  plotsvg.append("g").attr("class","yaxis")

function updatePlot() {
	currentID = d3.select(".selected").attr('id');
	yVals = timeDict[annDict[currentID]];
	timeVals =timeDict['Time']; 
	x.domain(d3.extent(timeVals));
	//y.domain(d3.extent(yVals));
	y.domain([-1,1]);


	result = [];

	for ( var i = 0; i < timeVals.length; i++ ) {
	  result.push( [ x(timeVals[i]), y(yVals[i]) ] );
	}
	//y.domain([0, d3.max(data, function(d) { return d.close; })]);
	var lineGenerator =  d3.line()
	var newline = lineGenerator(result)
	    //.x(timeVals)
	    //.y(yVals);

	d3.select(".line").attr("d", newline);
	d3.select(".xaxis").call(d3.axisBottom(x));
	d3.select(".yaxis").call(d3.axisLeft(y));


}

</script>

</body>

</html>
