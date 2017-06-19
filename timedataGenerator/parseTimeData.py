__author__ = 'sekark'

#An example file to load, parse, and convert to time course JSON.
#The final values should be between -1 and 1 to work on the dsj3 visualization

import csv
import numpy as np
import json

infile = 'exampledata.csv'
outfile = 'exampledata.json'


timeDict = {}

#parameters to play with
scalingFactor = 1.6;
offset = 60;

with open(infile, 'r') as csvfile:
    spamreader = csv.reader(csvfile, delimiter=";")
    for row in spamreader:
        if row[0] == 'Time':
            timeDict['Time'] = [float(i) for i in row[1:]]
        else:
            #timeDict[row[0]] = np.asarray([(float(i)-float(row[1]))*1/scalingFactor for i in row[1:]])

            timeDict[row[0]] = np.asarray([(np.log(float(i)+offset)-np.log(float(row[1])+offset))*1/scalingFactor for i in row[1:]])
            if np.amax(timeDict[row[0]]) > 1:
                print(np.amax(timeDict[row[0]]) )
            if np.amin(timeDict[row[0]]) < -1:
                print(np.amin(timeDict[row[0]]) )

            timeDict[row[0]][np.where(timeDict[row[0]] > 1)] = 1
            timeDict[row[0]][np.where(timeDict[row[0]] < -1)] = -1
            timeDict[row[0]] = timeDict[row[0]].tolist();
            #timeDict[row[0]][np.where(np.asarray(timeDict[row[0]]) < -1)] = -1
            #print(np.where(np.asarray(timeDict[row[0]]) > 1) )


print(timeDict)


with open(outfile, 'w') as out:
    json.dump(timeDict, out, indent=4, separators = (',', ': '))