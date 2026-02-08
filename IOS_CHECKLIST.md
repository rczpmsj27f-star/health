# iOS App Store Deployment Checklist

Use this checklist to ensure you complete all steps for a successful App Store submission.

## Pre-Development Setup

- [ ] macOS computer available
- [ ] Xcode installed (latest version)
- [ ] Apple Developer account enrolled ($99/year)
- [ ] Node.js and npm installed (v14+)
- [ ] CocoaPods installed (`sudo gem install cocoapods`)

## Initial Setup (One-Time)

- [ ] Dependencies installed (`npm install`)
- [ ] iOS platform added (`npx cap add ios`)
- [ ] CocoaPods dependencies installed (`cd ios/App && pod install`)
- [ ] Xcode project opens successfully (`npm run ios:open`)

## Development & Testing

- [ ] App runs in iOS Simulator
- [ ] Web content loads from https://ht.ianconroy.co.uk
- [ ] All features work correctly
- [ ] Splash screen displays properly
- [ ] Status bar styling is correct
- [ ] Keyboard behavior is appropriate
- [ ] Network connectivity handling works

## App Store Connect Setup

- [ ] App created in App Store Connect
  - [ ] App Name: Health Tracker
  - [ ] Bundle ID: co.uk.ianconroy.healthtracker
  - [ ] SKU: healthtracker-ios
  - [ ] Primary Language: English (UK)
  
- [ ] App Information completed
  - [ ] Category: Medical
  - [ ] Privacy Policy URL provided
  - [ ] Support URL provided
  - [ ] Subtitle entered
  - [ ] Keywords entered
  - [ ] Description entered

- [ ] Pricing and Availability set
  - [ ] Price tier selected
  - [ ] Countries selected

- [ ] App Privacy information completed
  - [ ] Data types listed
  - [ ] Data usage purposes explained
  - [ ] Tracking practices disclosed

## Assets Preparation

- [ ] App Icons created
  - [ ] 1024x1024 App Store icon (no transparency)
  - [ ] All required sizes generated
  - [ ] Icons added to Assets.xcassets

- [ ] Screenshots captured
  - [ ] iPhone 6.7" (1290 x 2796) - at least 1
  - [ ] iPhone 6.5" (1284 x 2778) - at least 1
  - [ ] iPhone 5.5" (1242 x 2208) - at least 1
  - [ ] iPad Pro 12.9" (2048 x 2732) - at least 1
  - [ ] iPad Pro 11" (1668 x 2388) - at least 1

## Code Signing

- [ ] App ID created in Apple Developer Portal
  - [ ] Bundle ID: co.uk.ianconroy.healthtracker
  - [ ] Capabilities configured (if needed)

- [ ] Distribution Certificate created
  - [ ] Certificate downloaded and installed

- [ ] Provisioning Profile created
  - [ ] App Store distribution profile
  - [ ] Downloaded and installed

- [ ] Signing configured in Xcode
  - [ ] Team selected
  - [ ] Automatic/Manual signing configured
  - [ ] No signing errors

## Pre-Build Verification

- [ ] Version number updated in Xcode
  - [ ] Version: 1.0.0 (or appropriate)
  - [ ] Build: 1 (increment for each submission)

- [ ] Bundle Identifier verified
  - [ ] Matches: co.uk.ianconroy.healthtracker

- [ ] Deployment target verified
  - [ ] iOS 13.0 minimum

- [ ] Capacitor synced
  - [ ] Run `npm run capacitor:sync`
  - [ ] No errors

## Building & Archiving

- [ ] Device selected: "Any iOS Device (arm64)"
- [ ] Product → Clean Build Folder
- [ ] Product → Archive
- [ ] Archive builds successfully
- [ ] Organizer opens with archive listed

## Upload to App Store Connect

- [ ] Archive selected in Organizer
- [ ] Distribute App → App Store Connect
- [ ] Upload selected (not Export)
- [ ] Signing options chosen
  - [ ] Automatically manage signing (recommended)
  - [ ] Or manual certificate/profile selected
- [ ] App uploaded successfully
- [ ] Processing complete in App Store Connect

## App Store Connect Submission

- [ ] Build selected for version
- [ ] All required information filled:
  - [ ] App Information
  - [ ] Pricing and Availability
  - [ ] App Privacy
  - [ ] Version Information
    - [ ] Screenshots uploaded
    - [ ] Description entered
    - [ ] What's New entered (for updates)
    - [ ] Promotional text (optional)
    - [ ] Keywords
  - [ ] Build selected
  - [ ] App Review Information
    - [ ] Contact information
    - [ ] Demo account (if required)
    - [ ] Notes for reviewer

- [ ] Export Compliance answered
  - [ ] App uses encryption: No (or Yes if applicable)
  - [ ] Or "None of the above apply"

- [ ] Content Rights verified
  - [ ] Rights to use content confirmed

- [ ] Advertising Identifier (IDFA) answered
  - [ ] Correctly answered based on usage

## Final Checks Before Submission

- [ ] All required fields have green checkmarks
- [ ] No red error indicators
- [ ] Screenshots look professional
- [ ] Description is clear and accurate
- [ ] Privacy policy is accessible
- [ ] Support URL works
- [ ] Demo account works (if provided)
- [ ] Contact information is current

## Submit for Review

- [ ] "Submit for Review" button clicked
- [ ] Confirmation received
- [ ] Status changed to "Waiting for Review"
- [ ] Email confirmation received

## After Submission

- [ ] Monitor status in App Store Connect
  - [ ] Waiting for Review
  - [ ] In Review (usually 24-48 hours)
  - [ ] Pending Developer Release (if approved)
  - [ ] Ready for Sale (after release)

- [ ] Respond to any review feedback promptly

- [ ] If rejected:
  - [ ] Review rejection reasons
  - [ ] Make necessary changes
  - [ ] Submit updated version

- [ ] If approved:
  - [ ] Release to App Store
  - [ ] Monitor downloads and reviews
  - [ ] Respond to user feedback

## Post-Release

- [ ] App appears in App Store
- [ ] Test download and installation
- [ ] Verify all features work
- [ ] Set up analytics (optional)
- [ ] Monitor crash reports
- [ ] Plan for updates and maintenance

## For Updates

When submitting an update:

- [ ] Increment build number
- [ ] Update version number (if significant changes)
- [ ] Update "What's New" section
- [ ] Test new features thoroughly
- [ ] Follow archive and upload steps above
- [ ] Submit new version for review

## Emergency Checklist

If app is rejected or has critical issues:

- [ ] Read rejection reason carefully
- [ ] Identify specific guidelines violated
- [ ] Make minimum necessary changes
- [ ] Test thoroughly
- [ ] Provide clear resolution notes to reviewers
- [ ] Resubmit promptly

## Resources

- **Apple Developer Portal:** https://developer.apple.com
- **App Store Connect:** https://appstoreconnect.apple.com
- **Review Guidelines:** https://developer.apple.com/app-store/review/guidelines/
- **Human Interface Guidelines:** https://developer.apple.com/design/human-interface-guidelines/

## Notes

Keep track of important dates and information:

- **First Submission Date:** ___________
- **Apple Developer Account Email:** ___________
- **Bundle ID:** co.uk.ianconroy.healthtracker
- **Current Version:** ___________
- **Current Build:** ___________
- **Next Planned Update:** ___________

---

**Reminder:** Keep this checklist updated as you go through the process. Check off items as you complete them to ensure nothing is missed.
