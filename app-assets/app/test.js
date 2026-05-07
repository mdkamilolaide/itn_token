
var vm = new Vue({
    el:"#app",
    data:{},
    methods:{
        clickInfo(){
            alert.Info('Testa','Testing to know if this will work');
        },
        clickError(){
            alert.Error('Testa','Testing to know if this will work');
        },
        clickSuccess(){
            alert.Success('Testa','Testing to know if this will work');
        },
        clickWarning(){
            alert.Warning('Testa','Testing to know if this will work');
        },
    },
    template:`
        <div>
            <h1>Ipolongo is now Vue compatible</h1>
            <p>
                <button type="button" class="btn round btn-primary" @click="clickSuccess()">Success</button>
                <button type="button" class="btn round btn-danger" @click="clickError()">Error</button>
                <button type="button" class="btn round btn-warning" @click="clickWarning()">Error</button>
                <button type="button" class="btn round btn-info" @click="clickInfo()">Error</button>
            </p>
        </div>
    `
});