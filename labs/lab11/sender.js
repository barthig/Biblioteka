require('dotenv').config();
const amqp = require('amqplib');

const amqpUrl = process.env.AMQP_URL || 'amqp://localhost';
const queueName = process.env.QUEUE_NAME || 'task_queue';

async function send() {
  const message = process.argv.slice(2).join(' ') || 'Hello World!';
  let connection;
  try {
    connection = await amqp.connect(amqpUrl);
    const channel = await connection.createChannel();
    await channel.assertQueue(queueName, { durable: true });
    channel.sendToQueue(queueName, Buffer.from(message), { persistent: true });
    console.log(`[x] Wysłano do ${queueName}: '${message}'`);
    await channel.close();
  } catch (error) {
    console.error('Błąd wysyłania wiadomości:', error.message);
  } finally {
    if (connection) {
      await connection.close().catch(() => {});
    }
  }
}

send();
