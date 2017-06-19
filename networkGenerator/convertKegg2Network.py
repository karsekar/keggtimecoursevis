__author__ = 'sekark'

# Convert Kegg Network to JSON for import into visualization
# Kegg metabolic networks can be downloaded from http://rest.kegg.jp/
# Save the file as an xml to use as the input for this script and generate the network JSON

import networkx as nx
import matplotlib.pyplot as plt
import xml.etree.ElementTree as ET
import json

inputXML = 'sce01100.xml'

#get all of the nodes by looking for compounds
tree = ET.parse(inputXML)
root = tree.getroot()
nodes = {}
for child in root:
    if child.attrib['type'] == 'compound':
        cid = child.attrib['name'].split(':')[1]
        nodes[cid] = {}

#get all of the edges by parsing reactions
edges = []
for reaction in root.iter('reaction'):
    substrates = []
    for substrate in reaction.iter('substrate'):
        substrates.append(substrate.attrib['name'].split(':')[1])
    for product in reaction.iter('product'):
        pname = product.attrib['name'].split(':')[1]
        for substrate in substrates:
            edges.append((substrate,pname))

#get the colors for the different compounds based of the KEGG classification
colors = []
for graphics in root.iter('graphics'):
    tempname = graphics.attrib['name']
    if tempname in nodes.keys():
        nodes[tempname]['x'] = graphics.attrib['x']
        nodes[tempname]['y'] = graphics.attrib['y']
        nodes[tempname]['color'] = graphics.attrib['fgcolor']
        color = graphics.attrib['fgcolor']
        if color not in colors:
            colors.append(color)
colors.remove('none')
colors.remove('#E0E0E0')

#draw the network, and make sure that it's ok
fig = plt.figure(figsize=(20,10))
Met = None
Met = nx.Graph()
nodes_of_int = []
xvals = []
yvals = []
positions = {}
for node in nodes:
    color = nodes[node]['color']
    if color in colors:
        nodes_of_int.append(node)
        positions[node] = (int(nodes[node]['x']),(2200 - int(nodes[node]['y'])))
        xvals.append(int(nodes[node]['x']))
        yvals.append((2200 - int(nodes[node]['y'])))
#pos=nx.get_node_attributes(Met,'pos')
#color_map = nx.get_node_attributes(Met,'color')
Met.add_nodes_from(nodes_of_int)

edges_of_int = []
for edge in edges:
    if edge[0] in nodes_of_int and edge[1] in nodes_of_int:
        edges_of_int.append(edge)

Met.add_edges_from(edges_of_int)

color_map = []
for node in Met.nodes():
    color = nodes[node]['color']
    color_map.append(color)

nx.draw_networkx_nodes(Met,pos = positions,node_color = color_map, node_size = 70)

#edges
nx.draw_networkx_edges(Met,pos = positions,width=0.25)

savename = inputXML.split('.')[0] + 'Network.pdf'
fig.savefig(savename)

#export into a network JSON
network = {"nodes":[],"links":[]}

for node in Met.nodes():
    network["nodes"].append({"label": node, "group": colors.index(nodes[node]['color'])+1, "x": positions[node][0], "y": positions[node][1]})

for edge in edges_of_int:
    network["links"].append({"source": edge[0], "target": edge[1], "value": 1, "x1": positions[edge[0]][0], "y1":
                             positions[edge[0]][1], "x2": positions[edge[1]][0], "y2": positions[edge[1]][1]})

jsonName = inputXML.split('.')[0] + 'Network.json'
json.dumps(network, indent=4, separators=(',', ': '))
with open(jsonName, 'w') as outfile:
    json.dump(network, outfile, indent=4, separators=(',', ': '))
