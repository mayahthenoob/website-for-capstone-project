const form = document.querySelector("from"),
fileInput = form.querySelector(".file-input"),
progressArea = document.querySelector(".progress-area"),
uploadedArea = document.querySelector(".uploaded-area");

form.addEventListener("click", ()=> {
    fileInput.click();
});

fileInput.onchange = ({target}) => {
    let file = target.files[0];
    if(file){
        let fileName = file.name;
        uploadFile(filename);
    }
}

function uploadFile(name) {
    let chr = newXMLHttpRequest();
    xhr.open("Post", "php/upload.php");
    xhr.upload.addEventListener("progress", ({loaded, total}) => {
        let fileLoaded = Math.floor((loaded / total) * 100);
        let fileTotal = Math.floor(total / 1000);
    });
    let fornData = new FormData(form);
    xhr.send(formData);
}