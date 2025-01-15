window.onload=function(){
    const canvas = document.getElementById('plotCanvas');
    const ctx = canvas.getContext('2d');
    
    const sizeWidth = ctx.canvas.clientWidth;
    const sizeHeight = ctx.canvas.clientHeight;

    let setupData = [];
    let hour_value = "";
    let minutes_value = "";
    let seconds_value = "";
    let day_value = "";
    let count = 0;


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
        url = `index.php?type=update&hour=${hour_value}&min=${minutes_value}&sec=${seconds_value}&day=${day_value}`;
        if (count > 20){
        fetch(type=url)
            .then(response =>response.json())
            .then(data =>{
                    ctx.clearRect (0 , 0 , sizeWidth , sizeHeight);
                    drawLines(setupData);
                    drawStations(setupData);
                    drawNames(setupData);
                    updatePosition(data);
            })
            .catch(error => console.error('Error fetching trip updates:', error));
            count = 0;
        }else{
            count += 1;
        }
        requestAnimationFrame(fetchTrainUpdates);
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

                // draw train id on top of it
                ctx.fillStyle = 'white';
                ctx.font = '15px sans serif';
                ctx.fillText(train.label, x, y - 40);
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

    let timeForm = document.getElementById("time_form");
    timeForm.addEventListener("submit", (e) => {
        e.preventDefault();
        hour_value = document.getElementById("hour").value;
        minutes_value = document.getElementById("minutes").value;
        seconds_value = document.getElementById("seconds").value;
        day_value = document.getElementById("day").value.toLowerCase();
        checkDayValue();
    });

    function checkDayValue(){
        weekdays = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"];
        if (!weekdays.includes(day_value)){
            day_value = "";
        }
    }
    fetchStationCoordinates();
    fetchTrainUpdates();

}
