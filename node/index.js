const app = require('express')();
const http = require('http').Server(app);
const io = require('socket.io')(http, {cors: {origin: "*"}});
const port = 3000;

const createClient = require('redis')['createClient'];
const client = createClient({
    socket: {
        port: 6379,
        host: 'redis'
    }
});
client.on('error', (err) => console.log('Redis Client Error', err));
async function run() {
    await client.connect();
    await client.subscribe('finish', (msg) => {
        io.emit('finish', msg);
    });
}
run();

io.on('connection', (socket) => {
    socket.on('chat message', msg => {
        io.emit('chat message', msg);
    });
    socket.on('finish', msg => {
        io.emit('finish', msg);
    });
    socket.on('competitors', msg => {
        io.emit('competitors', msg);
    });
});

http.listen(port, () => {
    console.log(`Socket.IO server running at http://node:${port}/`);
});