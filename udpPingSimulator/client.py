#Erikson Tocol
#UCID: et68
#Section: 002

import socket
from socket import *
import struct
import time

numOfPings = 10 #How many times to ping server
sequenceNum = 1 #Tracks what number ping this is

#Server's IP and port
serverIP = '127.0.0.1'
socketPort = 12000

print("Program start! Pinging IP " + serverIP +
      " at port " + str(socketPort) + ".")

#Create UDP socket
clientSocket = socket(AF_INET, SOCK_DGRAM)
clientSocket.settimeout(1) #Set timeout to 1 second

#Packet statistics
packetsSent = 0
packetsReceived = 0
minRTT = 0
maxRTT = 0
avgRTT = 0

#Sending packets
while sequenceNum <= numOfPings: #While there are still pings to send
    packetsSent += 1
    message = struct.pack("II", 1, sequenceNum)
    startTime = time.time()

    clientSocket.sendto(message, (serverIP, socketPort)) #Sends packet
    try:
        message, address = clientSocket.recvfrom(1024)
        packetsReceived += 1
        rtt = round((time.time() - startTime), 5)
        if rtt > maxRTT:
            maxRTT = rtt
        if rtt < minRTT or minRTT == 0:
            minRTT = rtt
        avgRTT += rtt
        print(str(sequenceNum) + ". RTT is " + str(rtt) + " seconds.")
    except:
        print(str(sequenceNum) + ". Message timed out.")
    sequenceNum += 1

#Print statistics
print("\nPackets sent: " + str(packetsSent))
print("Packets received: " + str(packetsReceived))
print("Packets lost: " + str((packetsSent - packetsReceived))
      + ", Loss rate: "
      + str(((packetsSent - packetsReceived)/packetsSent) * 100) + "%")
print("\nMinimum RTT: " + str(minRTT))
print("Maximum RTT: " + str(maxRTT))
print("Average RTT: " + str(avgRTT/packetsSent))
