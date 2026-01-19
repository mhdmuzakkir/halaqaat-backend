require('dotenv').config();
const express = require('express');
const cors = require('cors');
const mongoose = require('mongoose');
const app = express();

const port = process.env.PORT || 3000;

app.use(cors());
app.use(express.json());

mongoose.connect(process.env.MONGO_URI)
  .then(() => console.log('MongoDB connected'))
  .catch(err => console.error('Mongo error:', err));

// Test API
app.get('/api/test', (req, res) => {
  res.json({ message: 'Halaqaat backend LIVE on Render!' });
});

app.listen(port, () => {
  console.log(`Halaqaat backend on port ${port}`);
});
