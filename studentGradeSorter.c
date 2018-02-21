/*
Erikson Tocol
CS 288 2018S Section 006
HW 02
*/

#include <stdio.h>
#include <stdlib.h>

struct studentIdGradePair {
   int studentId;
   int studentGrade;
};

/* self-referential structure */
struct Node {
   struct studentIdGradePair pair;
	struct Node *next;
};

struct List {
	struct Node *head;
	struct Node *tail;
};


struct List SLL_new(){
	/* construct an empty list */
	struct List list;
	list.head = NULL;
	list.tail = NULL;
	return list;
}

int SLL_length(struct List *list) {
	/* return the number of pairs in the list */
	struct Node *p;

	int n = 0;
	for (p = list->head; p != NULL; p = p->next) {
		++n;
	}
	return n;
}

int SLL_empty(struct List *list) {
	/* return true if the list contains no pairs */
	return list->head == NULL;
}

struct studentIdGradePair SLL_pop(struct List *list) {
	/* remove and return the first pair of the list */
	struct Node *node = list->head;
	struct studentIdGradePair pair = node->pair;
	list->head = node->next;
	if (SLL_empty(list)) {
		list->tail = NULL;
	}
	free(node);
	return pair;
}

void SLL_clear(struct List *list) {
	/* remove all the pairs from the list */
	while (!SLL_empty(list)) {
		SLL_pop(list);
	}
}

void SLL_push(struct List *list, struct studentIdGradePair pair) {
	/*  insert the pair at the front of the list */
	struct Node *node = malloc(sizeof(struct Node));
	node->pair = pair;
	node->next = list->head;
	if (SLL_empty(list)) {
		list->tail = node;
	}
	list->head = node;
}

void SLL_append(struct List *list, struct studentIdGradePair pair) {
	/* append the pair to the end of the list */
	if (SLL_empty(list)) {
		SLL_push(list, pair);
	}
	else {
		struct Node *node = malloc(sizeof(struct Node));
		node->pair = pair;
		node->next = NULL;
		list->tail->next = node;
		list->tail = node;
	}
}

void SLL_appendToSpecificNode(struct Node *node, struct studentIdGradePair appendPair)
{  //Puts a appendNode in front of node
   struct Node *appendNode = malloc(sizeof(struct Node));//Creates the node
	appendNode->pair = appendPair;                        //Assigns id and grade to node
   appendNode->next = node->next;                        //Makes appending node point to what the node behind it was pointing to
	node->next = appendNode;                              //Behind node now points to the new appended node
}

int SLL_insert(struct List *list, struct studentIdGradePair insertPair) {
   //Checks if the studentId is already in a node, so you can push matching studentId's next to each other
   struct Node *tempNode = list->head;
   struct Node *slightlySmallerNode;
   
   while (tempNode != NULL) //While there are still nodes
   {
      if(tempNode->pair.studentId == insertPair.studentId)  //If there's a match
      {
         SLL_appendToSpecificNode(tempNode, insertPair);    //append a node right after the match
         return 0;                                          //then end function
      }
      if(tempNode->pair.studentId < insertPair.studentId)   //Tracks the node that our insert would be appended to
      {
         slightlySmallerNode = tempNode;
      }
      tempNode = tempNode->next;                            //Else, move onto next node
   }
   SLL_appendToSpecificNode(slightlySmallerNode, insertPair);  //If no match, then append node to the node it's slightly bigger than
}

void main(int argc, char *argv[]) {
   struct List list = SLL_new(); //Create list
   
   //Reading from files and inserting into list
   for (int i = 0; i < (argc - 1); ++i) //Loop to start reading files, stops after 4th argument
   {
      FILE *readFile = fopen(argv[i], "r");  //File to read
   	if (readFile != NULL)                  
      {
   		int studentId;
   		int grade;
   
   		while (fscanf(readFile, "%d,%d", &studentId, &grade) == 2)  //While the file still has pairs to read
         {
            struct studentIdGradePair pair = {studentId, grade};
            
            if(SLL_empty(&list))                                     //Pushes first node into empty list
               SLL_push(&list, pair);
            else                                                     //Inserts nodes in ascending studentId order
            {
               SLL_insert(&list, pair);
            }
   		}
   		fclose(readFile); 
   	}
   }
   
   //Writing to output file
   FILE *writeFile = fopen(argv[argc-1], "w");  //File to write to
   int printId;
   double addedPoints;
   
   while(!SLL_empty(&list))                     //Calculates averages and prints Id/Average pairs
   {
      for(int i = 0; i < 3; i++)                //Pops 3 test grades at a time and adds them to addedPoints
      {
         struct studentIdGradePair tempPair = SLL_pop(&list);
         printId = tempPair.studentId;
         addedPoints += tempPair.studentGrade;
      }
      
      struct studentIdGradePair writingPair = {printId, (int)((addedPoints/3) + 0.5)}; //Creates a pair for writing
      fprintf(writeFile, "%d,%d\n", writingPair.studentId, writingPair.studentGrade);  //Writes to file   
      printId = 0, addedPoints = 0;
   }
   fclose(writeFile);
}