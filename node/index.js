const app = require('express')();
const http = require('http').Server(app);
const io = require('socket.io')(http, {cors: {origin: "*"}});
const port = 3000;

const createClient = require('redis')['createClient'];
const clientRedis = createClient({
  socket: {
    port: 6379,
    host: 'redis'
  }
});
clientRedis.on('error', (err) => console.log('Redis Client Error', err));
const clientRedisPS = createClient({
  socket: {
    port: 6379,
    host: 'redis'
  }
});
clientRedisPS.on('error', (err) => console.log('Redis Client Error', err));

let clock, mode, timestamp, message;
let clients = [];
let stage = '{"type":"orient","gmt":"3"}';

async function run() {
  await clientRedis.connect();
  await clientRedis.subscribe('message', (msg) => {
    console.log(`REDIS message: ${msg}`);
    message = msg;
    io.emit('message', msg);
  });
  await clientRedis.subscribe('clock', (is_visible) => {
    console.log(`REDIS clock: ${is_visible}`);
    clock = is_visible;
    io.emit('clock', is_visible);
  });
  await clientRedis.subscribe('start', (is_visible) => {
    console.log(`REDIS start: ${is_visible}`);
    mode = ((is_visible == 'true') ? 'start' : null);
    io.emit('start', is_visible);
  });
  await clientRedis.subscribe('finish', (is_visible) => {
    console.log(`REDIS finish: ${is_visible}`);
    mode = ((is_visible == 'true') ? 'finish' : ((is_visible == 'first') ? 'finishfirst' : null));
    io.emit('finish', is_visible);
  });
  await clientRedis.subscribe('finished', (json) => {
    console.log(`REDIS finished: ${json}`);
    io.emit('finished', json);
  });
  await clientRedis.subscribe('list', (group_id) => {
    console.log(`REDIS list: ${group_id}`);
    mode = null;
    io.emit('list', group_id);
  });
  await clientRedis.subscribe('data', (json) => {
    console.log(`REDIS data: ${json}`);
    io.emit('data', json);
  });
  await clientRedis.subscribe('stage', (json) => {
    console.log(`REDIS stage: ${json}`);
    stage = json;
    io.emit('stage', json);
  });
  await clientRedis.subscribe('timestamp', (value) => {
    console.log(`REDIS timestamp: ${value}`);
    timestamp = value;
    io.emit('timestamp', value);
  });
  await clientRedis.subscribe('log', (json) => {
    console.log(`REDIS log: ${json}`);
    io.emit('log', json);
  });
  await clientRedis.subscribe('updatePlace', (array) => {
    console.log(`REDIS updatePlace: ${array}`);
    io.emit('updatePlace', array);
  });
}
run();
async function runStage(stage) {
  await clientRedisPS.connect();
  await clientRedisPS.set('stage', stage);
  await clientRedisPS.disconnect();
}
runStage(stage);

io.on('connection', (socket) => {
  let socket_id = socket.id;
  clients.push(socket_id);
  socket.on("disconnect", () => {
    clients = clients.filter((id) => {return id !== socket_id;})
  });
  socket.on('operation', value => {
    console.log(`IO operation: ${value}`);
    if (value == 'sync') {
      if (message)
        io.to(socket.id).emit('message', message);
      if (clock)
        io.to(socket.id).emit('clock', clock);
      if (timestamp)
        io.to(socket.id).emit('timestamp', timestamp);
      if (stage)
        io.to(socket.id).emit('stage', stage);
      if (mode == 'start')
        io.to(socket.id).emit('start', 'true');
      else if (mode == 'finish')
        io.to(socket.id).emit('finish', 'true');
      else if (mode == 'finishfirst')
        io.to(socket.id).emit('finish', 'first');
    }
  });
  socket.on('message', msg => {
    console.log(`IO message: ${msg}`);
    message = msg;
    clients.forEach((client) => {
      if (socket_id != client)
        io.to(client).emit('message', msg);
    });
  });
  socket.on('clock', is_visible => {
    console.log(`IO clock: ${is_visible}`);
    clock = is_visible;
    clients.forEach((client) => {
      if (socket_id != client)
        io.to(client).emit('clock', is_visible);
    });
  });
  socket.on('start', is_visible => {
    console.log(`IO start: ${is_visible}`);
    mode = ((is_visible == 'true') ? 'start' : null);
    clients.forEach((client) => {
      if (socket_id != client)
        io.to(client).emit('start', is_visible);
    });
  });
  socket.on('finish', is_visible => {
    console.log(`IO finish: ${is_visible}`);
    mode = ((is_visible == 'true') ? 'finish' : ((is_visible == 'first') ? 'finishfirst' : null));
    clients.forEach((client) => {
      if (socket_id != client)
        io.to(client).emit('finish', is_visible);
    });
  });
  socket.on('list', group_id => {
    console.log(`IO list: ${group_id}`);
    mode = null;
    clients.forEach((client) => {
      if (socket_id != client)
        io.to(client).emit('list', group_id);
    });
  });
  socket.on('data', json => {
    console.log(`IO data: ${json}`);
    clients.forEach((client) => {
      if (socket_id != client)
        io.to(client).emit('data', json);
    });
  });
  socket.on('stage', json => {
    console.log(`IO stage: ${json}`);
    stage = json;
    runStage(stage);
    clients.forEach((client) => {
      if (socket_id != client)
        io.to(client).emit('stage', json);
    });
  });
  socket.on('timestamp', value => {
    console.log(`IO timestamp: ${value}`);
    timestamp = value;
    clients.forEach((client) => {
      if (socket_id != client)
        io.to(client).emit('timestamp', value);
    });
  });
});

http.listen(port, () => {
  console.log(`Socket.IO server running at http://node:${port}/`);
});