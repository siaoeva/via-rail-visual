window.onload=function(){
    const canvas = document.getElementById('plotCanvas');
    const ctx = canvas.getContext('2d');

    function fetchStationCoordinates(){
        fetch('dummy.php')
            .then(response =>response.json())
            .then(data =>{
                const conversionFactor = 1;
                const convertedData = apply6

                drawLines(convertedData);
                drawStations(convertedData);
                drawNames(convertedData);
            })
            .catch(error => console.error('Error fetching coordinates:',error));
    }
    function applyConversionFactor(coords, factor) {
        return coords.map(coord => {
            return {
                x: coord.x * factor,
                y: coord.y * factor,
                label: coord.label
            };
        });
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
        ctx.font = '14px sans serif';
        coords.forEach(coord => {
            ctx.fillText(coord.label, coord.x + 10, coord.y + 10);
        });
    }
    fetchStationCoordinates();
}