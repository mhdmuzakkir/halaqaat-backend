const express = require('express');
const User = require('../models/User');
const Halqa = require('../models/Halqa');
const Student = require('../models/Student');
const Exam = require('../models/Exam');
const ExamMarks = require('../models/ExamMarks');
const AuditLog = require('../models/AuditLog');
const { protect, authorize } = require('../middleware/auth');

const router = express.Router();

// Middleware to verify admin
router.use(protect);
router.use(authorize('admin'));

// @route   POST /api/admin/user
// @desc    Create new user (Ustaaz, Mushrifeen, Mumtaheen)
router.post('/user', async (req, res) => {
  try {
    const { name, email, password, role, halqaAssigned } = req.body;

    let user = await User.findOne({ email });
    if (user) {
      return res.status(400).json({ message: 'User already exists' });
    }

    user = new User({
      name,
      email,
      password,
      role,
      halqaAssigned: halqaAssigned || []
    });

    await user.save();

    // Audit log
    await AuditLog.create({
      userId: req.user.id,
      action: 'Created user',
      details: { name, email, role }
    });

    res.status(201).json({ success: true, data: user });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

// @route   POST /api/admin/halqa
// @desc    Create new halqa
router.post('/halqa', async (req, res) => {
  try {
    const { name, halqaNumber, gender, ustaazId } = req.body;

    const halqa = new Halqa({
      name,
      halqaNumber,
      gender,
      ustaazId
    });

    await halqa.save();

    // Update user to assign halqa
    await User.findByIdAndUpdate(ustaazId, {
      $push: { halqaAssigned: halqa._id }
    });

    await AuditLog.create({
      userId: req.user.id,
      action: 'Created halqa',
      halqaId: halqa._id,
      details: { name, halqaNumber, gender }
    });

    res.status(201).json({ success: true, data: halqa });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

// @route   GET /api/admin/halqas
// @desc    Get all halqas
router.get('/halqas', async (req, res) => {
  try {
    const halqas = await Halqa.find()
      .populate('ustaazId', 'name email')
      .populate('mushrieenId', 'name email');

    res.status(200).json({ success: true, data: halqas });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

// @route   POST /api/admin/exam/create-exam-date
// @desc    Create exam date for specific halqa
router.post('/exam/create-exam-date', async (req, res) => {
  try {
    const { halqaId, examDate } = req.body;

    const exam = new Exam({
      halqaId,
      examDate,
      examStatus: 'pending'
    });

    await exam.save();

    await AuditLog.create({
      userId: req.user.id,
      action: 'Created exam date',
      halqaId,
      examId: exam._id,
      details: { examDate }
    });

    res.status(201).json({ success: true, data: exam });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

// @route   POST /api/admin/exam/assign-mumtaheen
// @desc    Assign Mumtaheen to exam
router.post('/exam/assign-mumtaheen', async (req, res) => {
  try {
    const { examId, mumtaheenId } = req.body;

    const exam = await Exam.findByIdAndUpdate(
      examId,
      { mumtaheenId },
      { new: true }
    ).populate('mumtaheenId', 'name email');

    await AuditLog.create({
      userId: req.user.id,
      action: 'Assigned Mumtaheen to exam',
      examId,
      details: { mumtaheenId }
    });

    res.status(200).json({ success: true, data: exam });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

// @route   PUT /api/admin/user/:id
// @desc    Update user
router.put('/user/:id', async (req, res) => {
  try {
    const { name, email, status } = req.body;

    const user = await User.findByIdAndUpdate(
      req.params.id,
      { name, email, status },
      { new: true }
    );

    res.status(200).json({ success: true, data: user });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

module.exports = router;