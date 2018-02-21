#Erikson Tocol
#UCID: et68
#Section: 002

import socket
from socket import *
import time
import random
import struct

#Socket IP and port
socketIP = "127.0.0.1"
socketPort = 12000

#Create UDP socket
serverSocket = socket(AF_INET, SOCK_DGRAM)
serverSocket.bind((socketIP, socketPort))

#Server waiting for client
while True:
    randomNum = random.randint(0, 10) #Generate random number to simulate packet loss
    recvMessage, address = serverSocket.recvfrom(1024) #Receive packet

    tempNum = 0 #Stores client message type number, it's 1 so we don't need it
    sequenceNum = 0 #Stores sequence number, we need to pack this

    tempNum, sequenceNum = struct.unpack("II", recvMessage) #Unpacks received message into variables
    sendMessage = struct.pack("II", 2, sequenceNum) #Creates message to send

    if randomNum < 4: #Simulates packet drop
        continue
    time.sleep((randomNum + 1) * 0.1) #Delay to simulate RTT

    serverSocket.sendto(sendMessage, address) #Server response
