# jyotish-lambda
kunjara/jyotish for AWS Lambda

### Deploy to Lambda

```bash
# customize serverless.yml as needed

# install dependencies
composer install
npm install

# deploy
serverless deploy
```
### Call Lambda Function

```bash
# install dependencies
cd test
npm install

# change AWS region in invokeLambda.js as needed

# run test script
node invokeLambda.js
```
