require('dotenv').config();
const express = require('express');
const cors = require('cors');
const mongoose = require('mongoose');
const helmet = require('helmet');

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(helmet());
app.use(cors());
app.use(express.json());

// Test endpoint
app.get('/api/test', (req, res) => {
  res.json({ message: 'Halaqaat Backend Live! Ready for Quran academy halqas.', timestamp: new Date().toISOString() });
});
app.get('/api/test', (req, res) => {
  res.json({ 
    message: 'Halaqaat Backend Live! 🚀', 
    status: 'ready for Quran academy halqas & kashf PDFs',
    timestamp: new Date().toISOString() 
  });
});

// MongoDB connection (add after deploy)
//mongoose.connect(process.env.MONGODB_URI || 'mongodb://localhost:27017/halaqaat', {
  //useNewUrlParser: true,
 // useUnifiedTopology: true
//})

// Basic 404
app.use('*', (req, res) => {
  res.status(404).json({ error: 'Route not found' });
});

app.listen(PORT, () => {
  console.log(`Halaqaat server on port ${PORT}`);
});
