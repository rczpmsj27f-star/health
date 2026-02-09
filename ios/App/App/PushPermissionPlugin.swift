import Foundation
import Capacitor
import OneSignalFramework

@objc(PushPermissionPlugin)
public class PushPermissionPlugin: CAPPlugin {
    
    @objc func requestPermission(_ call: CAPPluginCall) {
        DispatchQueue.main.async {
            do {
                OneSignal.Notifications.requestPermission({ accepted in
                    NSLog("OneSignal permission accepted: \(accepted)")
                    call.resolve([
                        "accepted": accepted
                    ])
                }, fallbackToSettings: true)
            } catch {
                NSLog("OneSignal permission request error: \(error)")
                call.reject("Failed to request permission", "\(error)")
            }
        }
    }
    
    @objc func checkPermission(_ call: CAPPluginCall) {
        DispatchQueue.main.async {
            let permission = OneSignal.Notifications.permission
            call.resolve([
                "permission": permission
            ])
        }
    }
}
