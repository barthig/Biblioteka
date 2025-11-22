require('dotenv').config();
const amqp = require('amqplib');

const amqpUrl = process.env.AMQP_URL || 'amqp://localhost';
const queueName = process.env.QUEUE_NAME || 'task_queue';

async function work() {
  let connection;
  try {
    connection = await amqp.connect(amqpUrl);
    const channel = await connection.createChannel();
    await channel.assertQueue(queueName, { durable: true });
    channel.prefetch(1);

    console.log(`[x] Oczekiwanie na wiadomości w kolejce ${queueName}. Aby zakończyć naciśnij CTRL+C`);

    channel.consume(
      queueName,
      (msg) => {
        if (!msg) {
          return;
        }
        const content = msg.content.toString();
        console.log(`[.] Otrzymano: '${content}'`);
        const seconds = content.split('.').length - 1;
        setTimeout(() => {
          console.log(`[v] Skończono: '${content}'`);
          channel.ack(msg);
        }, seconds * 1000);
      },
      { noAck: false }
    );
  } catch (error) {
    console.error('Błąd workera:', error.message);
    if (connection) {
      await connection.close().catch(() => {});
    }
    process.exit(1);
  }
}

work();
