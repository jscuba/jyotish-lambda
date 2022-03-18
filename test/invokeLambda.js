const AWS = require('aws-sdk');
const moment = require('moment-timezone');

const invokeLambda = (lambda, params) => new Promise((resolve, reject) => {
  lambda.invoke(params, (error, data) => {
    if (error) {
      reject(error);
    } else {
      resolve(data);
    }
  });
});

AWS.config.update({
    // region: process.env.AWS_DEFAULT_REGION // use for AWS
    region: 'us-west-2' // use for localhost
});

const lambda = new AWS.Lambda();

async function getTransitData(charttype, latitude, longitude, altitude, utcdate, timezone) {
  var datetime = utcdate.clone().tz(timezone);
  
  const params = {
    FunctionName: 'app-dev-draw-chart',
    InvocationType: 'RequestResponse',
    LogType: 'Tail',
    Payload: JSON.stringify({
      charttype,
      latitude,
      longitude,
      altitude,
      datetime
    }),
  };

  try {
    let result = await invokeLambda(lambda, params);
    if (result && result.Payload) {
      result = decodeURI(result.Payload);
      result = JSON.parse(result);
      result = JSON.parse(result);

      console.log(result);
    }
  } catch (err) {
    console.log(err);
  }
}

async function testIt() {
  let result = await getTransitData('D1', '33.0312528', '-117.2793571', '0.0', moment.utc(), 'America/Los_Angeles');
  console.log(result);
}

testIt();