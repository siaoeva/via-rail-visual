window.onload=function(){
    const canvas = document.getElementById('plotCanvas');
    const ctx = canvas.getContext('2d');

    let setupData = [];

    function fetchStationCoordinates(){
        fetch('index.php?type=setup')
            .then(response =>response.json())
            .then(data =>{
                setupData = data;
                drawLines(data);
                drawStations(data);
                drawNames(data);
            })
            .catch(error => console.error('Error fetching coordinates:',error));
    }

    function fetchTrainUpdates(){
        fetch('index.php?type=update')
            .then(response =>response.json())
            .then(data =>{
                updatePosition(data);
                requestAnimationFrame(fetchTrainUpdates);
            })
            .catch(error => console.error('Error fetching trip updates:', error));
    }
    function drawLines(coords){
        ctx.beginPath();
        ctx.moveTo(coords[0].x,coords[0].y);
        for (let i=1;i<coords.length;i++){
            ctx.lineTo(coords[i].x,coords[i].y);
        }
        ctx.strokeStyle = 'orange';
        ctx.lineWidth = 10;
        ctx.stroke();
    }
    function drawStations(coords) {
        ctx.fillStyle = 'white';
        coords.forEach(coord => {
            ctx.beginPath();
            ctx.arc(coord.x, coord.y, 5, 0, Math.PI * 2, false);
            ctx.fill();
        });
    }
    function drawNames(coords) {
        ctx.fillStyle = 'white';
        ctx.font = '20px sans serif';
        coords.forEach(coord => {
            ctx.fillText(coord.label, coord.x, coord.y + 40);
        });
    }
    function updatePosition(updateData) {
        for (let i = 0; i < updateData.length; i++){
            const train = updateData[i];
            if(train.running){
                let x = setupData[0].x;
                let y = setupData[0].y;
                for (let j = 0; j < setupData.length; j++){
                    if(train.prev_stop == setupData[j].label){
                        const thisStop = setupData[j];
                        x = thisStop.x;
                        y = thisStop.y;
                        if (train.progress!=0){
                            const nextStop = setupData[j+1];
                            x += (nextStop.x - x)*train.progress/100;
                            y += (nextStop.y - y)*train.progress/100;
                        }
                        break;
                    }
                }
                drawTrain(x , y);
            }
        }
    }
    function drawTrain(x , y){
        ctx.fillStyle='blue';
        const width = 20;
        const height = 20;

        const topLeftX=  x - width/2;
        const topLeftY = y - height/2;

        ctx.fillRect(topLeftX, topLeftY, width, height);
    }

    fetchStationCoordinates();
    fetchTrainUpdates();


}
