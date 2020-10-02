# Serverless image converter with AWS Lambda

This serverless image automatically converts to webp and resizes your images in S3 bucket.

#### Currently supported sizes

- 80x80 
- 200x200
- 400x400 

#### Deploy
```bash 
serverless deploy
```

#### Invoke with data 
```bash 
serverless invoke -f function -p lib/data.json
```

#### Prerequisites

Serverless.com package

``
npm install -g serverless
``

#### To do

- Parameterize image sizes
- Add exception to skip conversion and resize
