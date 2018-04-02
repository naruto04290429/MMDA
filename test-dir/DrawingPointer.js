// main();

//     function main() {
//         document.onmousemove = processMousePosition;
//         document.onkeypress = changeColor;
//         document.writeln("<p>");
//         document.writeln("<strong>Move the mouse in the above area.");
//         document.writeln("Press enter to change to erase mode;");
//         document.writeln("Press enter again to change to drawing mode</strong>");
//         document.writeln("</p>");
//     }

//     function processMousePosition(evt) {
//         draw(evt.pageX, evt.pageY);
//     }

//     function changeColor() {
//         if (color === "red") {
//             color = "white";
//             sideLength = 500;
//         } else {
//             color = "red";
//             sideLength = 5;
//         }
//     }

//     function draw(xPos, yPos) {
//         let context = document.getElementById("canvas").getContext("2d");
        
//         context.fillStyle = color;
//         context.fillRect(xPos, yPos, sideLength, sideLength);
//     }

function Recorder() {

    
}

function DoublyLinkedList(comparison_func) {
    this.start = null;
    this.end = null;
    this.comparison_func = comparison_func;
}

DoublyLinkedList.prototype = {
    constructor: DoublyLinkedList,
    // Add element to the front of the list.
    AddToFront: function(value) {
        new_node = new Node(value, this.start, null);
        if (this.isEmpty()) {
            this.end = new_node;
        }
        this.start = new_node;