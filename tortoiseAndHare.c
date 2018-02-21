#include <stdio.h>
#include <stdlib.h>
#include <time.h>

int tortPos = 1, harePos = 1; //Set position of tort and hare to square 1
int finishingLine = 70; //Set finishing line to square 70

void PrintCurrentPosition(int tortPos, int harePos);
void MoveTort(int *tortPos);
void MoveHare(int *harePos);

int main()
{
   srand(time(0)); //Seed the rand function
   
   printf("BANG !!!!!\nAND THEY'RE OFF !!!!!\n\n");
   
   //The race
   while(tortPos < finishingLine && harePos < finishingLine)
   {
      MoveTort(&tortPos);
      MoveHare(&harePos);
      PrintCurrentPosition(tortPos, harePos);
   }
   
   //Determine who won
   if(tortPos >= finishingLine && harePos >= finishingLine)
      printf("IT'S A TIE.");
   else if(tortPos >= finishingLine)
      printf("TORTOISE WINS!!! YAY!!!");
   else if(harePos >= finishingLine)
      printf("HARE WINS. YUCH.");
}

void MoveTort(int *tortPos)
{
   int r;
   r = (rand() % 10) + 1;  //Randomly generates 1 through 10
   //printf("%d ", r);     //Debugging print to check actions to number
   
   if(r >= 1 && r <= 5)       //50% Fast Plod
      *tortPos += 3;
   else if(r >= 6 && r <= 7)  //20% Slip
      *tortPos -= 6;
   else if(r >= 8 && r <= 10) //30% Slow Plod
      *tortPos += 1;
   
   if(*tortPos < 1)
      *tortPos = 1;
}

void MoveHare(int *harePos)
{
   int r;
   r = (rand() % 10) + 1; //Randomly generates 1 through 10
   //printf("%d ", r);    //Debugging print to check actions to number
   
   if(r >= 1 && r <= 2)       //20% Sleep
      ;
   else if(r >= 3 && r <= 4)  //20% Big Hop
      *harePos += 9;
   else if(r == 5)            //10% Big Slip
      *harePos -= 12;
   else if(r >=6 && r <= 8)   //30% Small Hop      
      *harePos += 1;
   else if(r >= 9 && r <= 10) //20% Small Slip
      *harePos -= 2;
      
   if(*harePos < 1)
      *harePos = 1;
}

void PrintCurrentPosition(int tortPos, int harePos) //Prints current position of tortoise and hare
{
   int i;
   
   if(tortPos == harePos) //If tort and hare on same space
   {
      for(i = 1; i < tortPos; i++)
      {
         printf("%s", " ");
      }
      printf("OUCH!!!");
      for(i = 1; i <= ((finishingLine - tortPos) - 6); i++)
      {
         printf("%s", " ");
      }
   }
   else if(tortPos < harePos) //If tort is behind hare
   {
      for(i = 1; i < tortPos; i++) //Prints blanks until
      {
         printf("%s", " ");
      }
      printf("T"); //gets to tort's position, and then prints tort's position
      for(i = 1; i < (harePos - tortPos); i++)
      {
         printf("%s", " ");
      }
      printf("H");
      for(i = 1; i <= (finishingLine - harePos); i++) //Prints rest of blanks in line
      {
         printf("%s", " ");
      }
   }
   else if(harePos < tortPos) //If hare is behind tort
   {
      for(i = 1; i < harePos; i++)
      {
         printf("%s", " ");
      }
      printf("H");
      for(i = 1; i < (tortPos - harePos); i++)
      {
         printf("%s", " ");
      }
      printf("T");
      for(i = 1; i <= (finishingLine - tortPos); i++)
      {
         printf("%s", " ");
      }
   }
   
   printf("\n"); //New line for cleaner printing
}