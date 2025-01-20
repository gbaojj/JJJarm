-- Optimized Script to Reduce Memory Leak Issues
local Fluent = loadstring(game:HttpGet("https://github.com/dawid-scripts/Fluent/releases/latest/download/main.lua"))()
local SaveManager = loadstring(game:HttpGet("https://raw.githubusercontent.com/dawid-scripts/Fluent/master/Addons/SaveManager.lua"))()
local InterfaceManager = loadstring(game:HttpGet("https://raw.githubusercontent.com/dawid-scripts/Fluent/master/Addons/InterfaceManager.lua"))()

game:GetService("ReplicatedStorage").Packages.Knit.Services.WrestleService.RF.OnAutoFight:InvokeServer()

local Window = Fluent:CreateWindow({
    Title = "Arm ",
    SubTitle = "by gbao",
    TabWidth = 160,
    Size = UDim2.fromOffset(580, 460),
    Acrylic = true, 
    Theme = "Dark",
    MinimizeKey = Enum.KeyCode.LeftControl
})

local Tabs = {
    Main = Window:AddTab({ Title = "Main", Icon = "" }),
    Settings = Window:AddTab({ Title = "Settings", Icon = "settings" })
}

local Options = Fluent.Options
local VirtualInputManager = game:GetService("VirtualInputManager")
local npcPath = workspace.GameObjects.RngNPCs.BlossomVillage.Npc

local isAutoFarming = false
local activeThreads = {}

-- Helper to manage threads
local function stopThread(threadName)
    if activeThreads[threadName] then
        activeThreads[threadName] = false
    end
end

local function startThread(threadName, func)
    stopThread(threadName) -- Stop any existing thread with the same name
    activeThreads[threadName] = true
    task.defer(function()
        while activeThreads[threadName] do
            func()
        end
    end)
end

-- Function to update proximity prompts
local function updateProximityPrompts(folder)
    for _, descendant in ipairs(folder:GetDescendants()) do
        if descendant:IsA("ProximityPrompt") then
            descendant.HoldDuration = 0 -- Đặt thời gian giữ phím E = 0
            descendant.Style = Enum.ProximityPromptStyle.Default
            descendant.MaxActivationDistance = 6 -- Tăng khoảng cách kích hoạt
        end
    end
end


-- Function for Auto NPC Farming
local function interactWithNPC()
    task.wait(0.1) -- Đợi một chút trước khi gửi phím
    VirtualInputManager:SendKeyEvent(true, Enum.KeyCode.E, false, game)
    task.wait(0.1)
    VirtualInputManager:SendKeyEvent(false, Enum.KeyCode.E, false, game)
end

local AutoBeatNPCToggle = Tabs.Main:AddToggle("AutoBeatNPC", {
    Title = "Auto Beat NPC",
    Default = false
})

AutoBeatNPCToggle:OnChanged(function()
    isAutoFarming = AutoBeatNPCToggle.Value

    if isAutoFarming then
        -- Thay vòng lặp bằng sự kiện ChildAdded
        npcPath.ChildAdded:Connect(function(child)
            if child:IsA("Model") and child:FindFirstChild("Table") and child.Table:FindFirstChild("PlayerRoot") then
                updateProximityPrompts(child) -- Cập nhật ProximityPrompt cho NPC mới
                local humanoid = game.Players.LocalPlayer.Character:FindFirstChild("HumanoidRootPart")
                if humanoid then   
                    humanoid.CFrame = child.Table.PlayerRoot.CFrame
                    task.wait(0.5) -- Short delay to avoid overlapping actions
                    interactWithNPC()
                    task.wait(3.5)
                end
            end
        end)
    else
        stopThread("NPCFarm")
    end
end)

-- Function for Auto Buying
local AutoBuyToggle = Tabs.Main:AddToggle("AutoBuyToggle", {
    Title = "Auto Buy (Selected Slots)",
    Default = false
})

local BlackMarketDropdown = Tabs.Main:AddDropdown("BlackMarketDropdown", {
    Title = "Select Merchant Slot(s)",
    Values = {1, 2, 3, 4, 5},
    Multi = true,
    Default = {}
})

AutoBuyToggle:OnChanged(function()
    if AutoBuyToggle.Value then
        startThread("AutoBuy", function()
            for i = 1, 3 do
                for number, isSelected in pairs(BlackMarketDropdown.Value) do
                    if not AutoBuyToggle.Value then break end
                    if isSelected then
                        local args = {
                            [1] = "Blossom Merchant",
                            [2] = number
                        }
                        game:GetService("ReplicatedStorage").Packages.Knit.Services.LimitedMerchantService.RF.BuyItem:InvokeServer(unpack(args))
                        task.wait(10)
                    end
                end
            end
            task.wait(90) -- Wait 90 seconds before next cycle
        end)
    else
        stopThread("AutoBuy")
    end
end)

-- Function for Auto Spin
local AutoSpinToggle = Tabs.Main:AddToggle("AutoSpin", {
    Title = "Auto Spin",
    Default = false
})

AutoSpinToggle:OnChanged(function()
    if AutoSpinToggle.Value then
        startThread("AutoSpin", function()
            local args = {
                [1] = "Ninja Fortune",
                [2] = "x25"
            }
            game:GetService("ReplicatedStorage").Packages.Knit.Services.SpinnerService.RF.Spin:InvokeServer(unpack(args))
            task.wait(3)
        end)
    else
        stopThread("AutoSpin")
    end
end)

-- Function for Auto Claim Daily Reward
local AutoClaimRewardToggle = Tabs.Main:AddToggle("AutoClaimReward", {
    Title = "Auto Claim Daily Reward",
    Default = false
})

AutoClaimRewardToggle:OnChanged(function()
    if AutoClaimRewardToggle.Value then
        startThread("AutoClaimReward", function()
            game:GetService("ReplicatedStorage").Packages.Knit.Services.DailyRewardService.RE.onClaimReward:FireServer()
            task.wait(1800) -- Wait 30 minutes before claiming again
        end)
    else
        stopThread("AutoClaimReward")
    end
end)


-- SaveManager and InterfaceManager Setup
SaveManager:SetLibrary(Fluent)
InterfaceManager:SetLibrary(Fluent)
SaveManager:IgnoreThemeSettings()
SaveManager:SetIgnoreIndexes({})
InterfaceManager:SetFolder("FluentScriptHub")
SaveManager:SetFolder("FluentScriptHub/specific-game")

InterfaceManager:BuildInterfaceSection(Tabs.Settings)
SaveManager:BuildConfigSection(Tabs.Settings)
Window:SelectTab(1)

Fluent:Notify({
    Title = "Fluent",
    Content = "The script has been loaded.",
    Duration = 8
})

-- Load Auto-Config
SaveManager:LoadAutoloadConfig()
