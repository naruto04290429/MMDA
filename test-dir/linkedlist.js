'use-strict'

// Implement a doubly-linked list custom type that defines the following operations:

function Node(value, next, prev) {
    this.value = value;
    this.next = null;
    this.prev = null;
}

// A constructor that takes a comparison function as parameter. This function supports look up / insertion operations.

function DoublyLinkedList(comparison_func) {
    this.first_node = null;
    this.last_node = null;
    this.comparison_func = comparison_func;
}

DoublyLinkedList.prototype = {
    constructor: DoublyLinkedList,
    InsertBefore: function(node, newNode) {
        newNode.next = node;
        if (node.prev == null) {
            newNode.prev = null;
            this.first_node = newNode;
        } else {
            newNode.prev = node.prev;
            node.prev.next = newNode;
        }
        node.prev = newNode;
    },
    InsertBeginning: function(newNode) {
        if (this.first_node == null) {
            this.first_node = newNode;
            this.last_node = newNode;
        } else {
            this.InsertBefore(this.first_node, newNode);
        }
    },
    InsertEnd: function(newNode) {
        if (this.last_node == null) {
            this.InsertBeginning(newNode);
        } else {
            this.InsertAfter(this.last_node, newNode);
        }
    },
    InsertAfter: function(node, newNode) {
        newNode.prev = node
        if (node.next == null) {
            newNode.next = null
            this.last_node = newNode
        } else {
            newNode.next = node.next;
            node.next.prev = newNode;
        }
        node.next = newNode;
    },
    randomize: function() {
        arr = this.OutputAsList();
        arr = this.shuffle(arr);
        console.log(arr);
        this.first_node = this.last_node = null;
        for (i = 0; i < arr.length; i++) {
            newNode = new Node(arr[i]);
            this.InsertEnd(newNode);
        }
    },
    shuffle: function(array) {
        //based off the Fisher–Yates shuffle
        for (i = 0; i < array.length - 1; i++) {
            let j = Math.floor(Math.random() * i);

            let t = array[i];
            array[i] = array[j];
            array[j] = t;
        }      
        return array;
    },
    // Insert element in-between two elements.
    InsertBetween: function(prevNode, nextNode, newNode) {
        prevNode.next = newNode;
        newNode.next = nextNode;
    },
    Search: function(needle) {
      let curr = this.first_node;
      while (curr != null) {
          if (this.comparison_func(curr.value, needle) == 0) {
              return curr;
          }
      }
      return False;
    },
    // Size of the list
    Size: function() {
        size = 0;
        curr = this.start;
        while (curr != null) {
            size += 1;
            curr = curr.next;
        }
        return size;
    },
    // Whether the list is empty or not.
    isEmpty: function() {
        return this.Size() == 0;
    },
    OutputAsList: function() {
        curr = this.first_node;

        arr = [];

        while(curr != null) {
            arr.push(curr.value);
            curr = curr.next;
        }

        console.log(arr);
        return arr;
    }
};

function Utility(utilityName, utilityDescription) {
    this.utilityName = utilityName;    
    this.utilityDescription = utilityDescription;
}

Utility.prototype = {
    constructor: Utility,
    info: function() {
        console.log("Utility Name: " + this.utilityName);
        console.log("Utility Description: " + this.utilityDescription);
    }
};

function Viewer(utilityName, utilityDescription) {
    Utility.call(this, utilityName, utilityDescription);
    this.setupPhotoArray("umcp/", "college", 1, 1);
    this.intervalID = null;
}

Viewer.prototype = new Utility();

Viewer.prototype.constructor = Viewer;
Viewer.prototype.resetPhotoViewer = function() {
    this.setupPhotoArray("umcp/", "college", 1, 1);
    this.intervalID = null;
    this.getNextPhoto();
}
Viewer.prototype.setupPhotoArray = function(folder_name, common_name, start, end) {
    this.photoArray = this.getArrayPhotosNames(folder_name, common_name, start, end);
    this.curr = this.photoArray.first_node;
},
Viewer.prototype.getArrayPhotosNames = function(folder_name, common_name, start, end) {
    
    if (!isNaN(start) && !isNaN(end) && end >= start) {
        let f = (x,y) => x.localeCompare(y);
        let arr = new DoublyLinkedList(f);
        for (i = start; i <= end; i++) {
            let x = new Node(folder_name + common_name + i);
            arr.InsertEnd(x);
        }
        return arr;
    } else {
        alert("Invalid Range!"); 
    }


}
Viewer.prototype.randomCyclePhotos = function() {
    this.randomize();
    this.cyclePhotos();
}
Viewer.prototype.cyclePhotos = function() {
    this.stopCyclingPhotos();
    this.intervalID = setInterval(this.getNextPhoto.bind(this), 1000);
}
Viewer.prototype.stopCyclingPhotos = function() {
    clearInterval(this.intervalID);  
}
Viewer.prototype.getNextPhoto = function() {
    path = this.getNextPath();

    document.getElementById("photo").innerHTML = "<img height=\"400\"  src=\"" + path + ".jpg\" />";
},
Viewer.prototype.getNextPath = function() {
    var path = this.curr.value;
    if (this.curr.next == null) {
        this.curr = this.photoArray.first_node;
    } else {
        this.curr = this.curr.next;
    }
    return path;
}
Viewer.prototype.getPrevPhoto = function() {
    path = this.getPrevPath();
    console.log(this.photoArray);
    console.log(this.curr);
    document.getElementById("photo").innerHTML = "<img height=\"400\"  src=\"" + path + ".jpg\" />";
}
Viewer.prototype.getPrevPath = function() {
    var path = this.curr.value;
    if (this.curr.prev == null){
        this.curr = this.photoArray.last_node;
    } else {
        this.curr = this.curr.prev;
    }
    return path;
}
Viewer.prototype.randomize = function() {
    this.photoArray.randomize();
    this.curr = this.photoArray.first_node;
    this.getNextPhoto();
}

function Recorder(utilityName, utilityDescription) {
    Utility.call(this, utilityName, utilityDescription);
    this.queue = new DoublyLinkedList();
    this.canvas = document.createElement("CANVAS");
    this.canvas.onmousemove = this.processMousePosition.bind(this);
    this.canvas.width = 500;
    this.canvas.height = 500;


    document.getElementById("canvasdiv").appendChild(this.canvas);
    this.currentlyRecording = false;
};

Recorder.prototype = new Utility();

Recorder.prototype.constructor = Recorder;
Recorder.prototype.onmousemove = function(evt) {
    this.processMousePosition(evt);
};
Recorder.prototype.processMousePosition = function(evt) {
    this.draw(evt.pageX, evt.pageY);
    if (!this.currentlyRecording) {
        return;
    }
    newObj = {
        X: evt.pageX,
        Y: evt.pageY,
        Color: document.getElementById("drawColor").value
    };
    newNode = new Node(newObj);
    this.queue.InsertEnd(newNode);
};
Recorder.prototype.draw = function(xPos, yPos) {
    let context = this.canvas.getContext("2d");
    context.fillStyle = document.getElementById("drawColor").value;
    context.fillRect(xPos, yPos, 5, 5);
}; 
Recorder.prototype.play = function() {
    let context = this.canvas.getContext("2d");
    context.fillStyle = "white";        
    context.fillRect(0, 0, this.canvas.width, this.canvas.height);
    console.log(this.queue.OutputAsList());
    let curr = this.queue.first_node;
    setInterval(() => {
        if (curr) {
            context.fillStyle = curr.value.Color;
            context.fillRect(curr.value.X, curr.value.Y, 5, 5);
            curr = curr.next;        
        } else {
            clearInterval();
        }
    }, 20);
};
Recorder.prototype.stopRecording = function() {
    this.currentlyRecording = false;
};
Recorder.prototype.startRecording = function() {
    this.currentlyRecording = true;
};
Recorder.prototype.clearScreen = function() {
    let context = this.canvas.getContext("2d");        
    context.fillStyle = "white";        
    context.fillRect(0, 0, this.canvas.width, this.canvas.height);
};
Recorder.prototype.saveRecording = function() {
    let output_list = this.queue.OutputAsList();
    localStorage.setItem("saved_recording", JSON.stringify(output_list));
};
Recorder.prototype.loadRecording = function() {
    let input_list = JSON.parse(localStorage.getItem("saved_recording"));
    this.queue = new DoublyLinkedList();

    for (i = 0; i < input_list.length; i++) {
        this.queue.InsertEnd(new Node(input_list[i]));
    }
};
Recorder.prototype.reset = function() {
    this.queue = new DoublyLinkedList();
    this.clearScreen();
};

function processData() {
    let action = document.getElementById('action').value;

    let folder_name = document.getElementById("folder_name").value;
    let common_name = document.getElementById("common_name").value;
    let start_photo = document.getElementById("start_photo").value;
    let end_photo = document.getElementById("end_photo").value;

    x.setupPhotoArray(folder_name, common_name, start_photo, end_photo);
    x.getNextPhoto();
}