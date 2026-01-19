const express = require('express');
const cors = require('cors');
require('dotenv').config();

const app = express();

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Import routes
const authRoutes = require('./routes/auth');
const adminRoutes = require('./routes/admin');
const ustaazRoutes = require('./routes/ustaaz');
const mushrieenRoutes = require('./routes/mushrifeen');
const mumtaheenRoutes = require('./routes/mumtaheen');
const reportRoutes = require('./routes/reports');

// Use routes
app.use('/api/auth', authRoutes);
app.use('/api/admin', adminRoutes);
app.use('/api/ustaaz', ustaazRoutes);
app.use('/api/mushrifeen', mushrieenRoutes);
app.use('/api/mumtaheen', mumtaheenRoutes);
app.use('/api/reports', reportRoutes);

// Health check
app.get('/api/health', (req, res) => {
  res.json({ status: 'Server running' });
});

module.exports = app;
